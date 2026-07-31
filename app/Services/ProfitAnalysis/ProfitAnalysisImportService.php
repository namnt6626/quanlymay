<?php

namespace App\Services\ProfitAnalysis;

use App\Models\ProfitAnalysisPeriod;
use App\Models\ProfitAnalysisSkuMap;
use App\Models\ProfitAnalysisSkuSummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProfitAnalysisImportService
{
    public function __construct(private readonly SimpleXlsxReader $reader) {}

    /**
     * @param array<string, string> $files
     * @return array<string, mixed>
     */
    public function preview(array $files, Carbon $periodMonth, float $adCostPerOrder = 0): array
    {
        $fob = $this->parseFob($files['fob_file']);
        $analytics = $this->emptyAnalytics();
        $settlement = $this->parseSettlement($files['settlement_file']);
        $orders = $this->parseOrderSkuList($files['order_file'], $settlement['orders_by_id'] ?? []);
        $adCost = max(0, $adCostPerOrder) * (int) $orders['unique_orders'];
        $ads = $this->manualAds($adCostPerOrder, (int) $orders['unique_orders'], $adCost);
        $settlementSource = $settlement;
        unset($settlementSource['orders_by_id']);

        $skuRows = [];
        foreach ($orders['sku_rows'] as $sellerSku => $row) {
            $suggestion = $this->suggestSkuMap($sellerSku, $row['product_name'], $fob);

            $row['fob_sku'] = $suggestion['fob_sku'] ?? null;
            $row['fob_code'] = $suggestion['fob_code'] ?? null;
            $row['unit_cost'] = (float) ($suggestion['unit_cost'] ?? 0);
            $row['map_source'] = $suggestion['source'] ?? 'manual';
            $row['confidence'] = $suggestion['confidence'];
            $row['map_reason'] = $suggestion['reason'];
            $row['needs_cost'] = $row['net_quantity'] > 0 && $row['unit_cost'] <= 0;
            $skuRows[] = $row;
        }

        usort($skuRows, fn (array $a, array $b): int => $b['net_quantity'] <=> $a['net_quantity']);

        $periodStart = $settlement['period_start'] ?? $orders['period_start'] ?? null;
        $periodEnd = $settlement['period_end'] ?? $orders['period_end'] ?? null;

        return [
            'period' => [
                'month' => $periodMonth->copy()->startOfMonth()->toDateString(),
                'label' => 'T'.$periodMonth->format('n/Y'),
                'detected_start' => $periodStart,
                'detected_end' => $periodEnd,
                'existing_period_id' => ProfitAnalysisPeriod::query()
                    ->whereDate('period_month', $periodMonth->copy()->startOfMonth()->toDateString())
                    ->value('id'),
            ],
            'source_totals' => [
                'analytics' => $analytics,
                'ads' => $ads,
                'settlement' => $settlementSource,
                'orders' => [
                    'row_count' => $orders['row_count'],
                    'unique_orders' => $orders['unique_orders'],
                    'file_unique_orders' => $orders['file_unique_orders'],
                    'sku_count' => count($skuRows),
                    'status_counts' => $orders['status_counts'],
                    'settlement_filter' => $orders['settlement_filter'],
                ],
                'fob' => [
                    'sku_count' => count($fob['items']),
                ],
            ],
            'summary' => [
                'sku_count' => count($skuRows),
                'missing_cost_count' => collect($skuRows)->where('needs_cost', true)->count(),
                'auto_mapped_count' => collect($skuRows)->whereIn('confidence', ['HIGH', 'SAVED'])->where('needs_cost', false)->count(),
                'order_count' => (int) $orders['unique_orders'],
                'completed_order_count' => (int) $orders['unique_orders'],
                'analytics_order_count' => (int) $analytics['orders'],
                'item_count' => (int) collect($skuRows)->sum('quantity_sold'),
                'gmv' => (float) $analytics['gmv'],
                'settlement_revenue' => (float) $settlement['total_revenue'],
                'sku_gross_revenue_total' => (float) collect($skuRows)->sum(fn (array $row): float => $row['revenue'] + $row['refund_amount']),
                'sku_refund_total' => (float) collect($skuRows)->sum('refund_amount'),
                'sku_revenue_total' => (float) collect($skuRows)->sum('revenue'),
                'revenue_adjustment' => (float) $settlement['total_revenue'] - (float) collect($skuRows)->sum('revenue'),
                'marketplace_fees' => abs((float) $settlement['total_fees']),
                'ad_cost' => (float) $ads['ad_cost'],
                'ad_cost_per_order' => (float) $ads['cost_per_order'],
            ],
            'sku_rows' => $skuRows,
        ];
    }

    /**
     * @param array<string, mixed> $preview
     * @param array<string, array<string, mixed>> $skuInputs
     */
    public function commit(array $preview, array $skuInputs, int $userId): ProfitAnalysisPeriod
    {
        $skuRows = [];
        $missing = [];
        $missingSettlementRevenue = abs((float) data_get($preview, 'source_totals.orders.settlement_filter.missing_revenue', 0));

        if ($missingSettlementRevenue > 0.5) {
            throw new RuntimeException('File tất cả đơn hàng/SKU đang thiếu đơn có doanh thu trong file quyết toán. Vui lòng upload file đơn hàng bao phủ đủ các mã đơn quyết toán.');
        }

        foreach ($preview['sku_rows'] as $row) {
            $input = $skuInputs[$row['key']] ?? [];
            $unitCost = $this->number($input['unit_cost'] ?? $row['unit_cost'] ?? 0);
            $fobSku = trim((string) ($row['fob_sku'] ?? ''));

            $row['unit_cost'] = (float) $unitCost;
            $row['fob_sku'] = $fobSku !== '' ? $fobSku : null;
            $row['map_source'] = ($row['confidence'] ?? '') === 'HIGH' && $unitCost > 0 ? 'auto' : 'manual';
            $row['needs_cost'] = $row['net_quantity'] > 0 && $unitCost <= 0;

            if ($row['needs_cost']) {
                $missing[] = $row['seller_sku'];
            }

            $skuRows[] = $row;
        }

        if ($missing !== []) {
            throw new RuntimeException('Còn SKU thiếu giá vốn: '.implode(', ', array_slice($missing, 0, 8)));
        }

        $totals = $this->calculateTotals($preview, $skuRows);

        return DB::transaction(function () use ($preview, $skuRows, $totals, $userId): ProfitAnalysisPeriod {
            $periodMonth = Carbon::parse($preview['period']['month'])->startOfMonth()->toDateString();

            ProfitAnalysisPeriod::query()
                ->whereDate('period_month', $periodMonth)
                ->each(function (ProfitAnalysisPeriod $period): void {
                    $period->delete();
                });

            foreach ($skuRows as $row) {
                ProfitAnalysisSkuMap::query()->updateOrCreate(
                    ['seller_sku' => $row['seller_sku']],
                    [
                        'fob_sku' => $row['fob_sku'],
                        'fob_code' => $row['fob_code'],
                        'product_name' => $row['product_name'],
                        'unit_cost' => $row['unit_cost'],
                        'source' => $row['map_source'],
                        'status' => $row['unit_cost'] > 0 ? 'mapped' : 'missing_cost',
                        'note' => $row['map_reason'] ?? null,
                    ]
                );
            }

            $period = ProfitAnalysisPeriod::query()->create([
                'period_month' => $periodMonth,
                'period_start' => $preview['period']['detected_start'],
                'period_end' => $preview['period']['detected_end'],
                'label' => $preview['period']['label'],
                'sku_count' => count($skuRows),
                'missing_cost_count' => 0,
                'order_count' => $totals['order_count'],
                'item_count' => $totals['item_count'],
                'gmv' => $totals['gmv'],
                'settlement_revenue' => $totals['settlement_revenue'],
                'sku_gross_revenue_total' => $totals['sku_gross_revenue_total'],
                'sku_refund_total' => $totals['sku_refund_total'],
                'sku_revenue_total' => $totals['sku_revenue_total'],
                'revenue_adjustment' => $totals['revenue_adjustment'],
                'marketplace_fees' => $totals['marketplace_fees'],
                'ad_cost' => $totals['ad_cost'],
                'cogs' => $totals['cogs'],
                'total_revenue' => $totals['total_revenue'],
                'total_cost' => $totals['total_cost'],
                'profit' => $totals['profit'],
                'profit_per_order' => $totals['profit_per_order'],
                'ad_breakeven' => $totals['ad_breakeven'],
                'completed_order_count' => $totals['completed_order_count'],
                'analytics_order_count' => $totals['analytics_order_count'],
                'source_totals' => $preview['source_totals'],
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            foreach ($totals['sku_summaries'] as $summary) {
                $period->skuSummaries()->create($summary);
            }

            return $period;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $skuRows
     * @return array<string, mixed>
     */
    private function calculateTotals(array $preview, array $skuRows): array
    {
        $summary = $preview['summary'];
        $skuRevenue = (float) collect($skuRows)->sum('revenue');
        $skuRefundTotal = (float) collect($skuRows)->sum('refund_amount');
        $skuGrossRevenue = $skuRevenue + $skuRefundTotal;
        $marketplaceFees = (float) $summary['marketplace_fees'];
        $adCost = (float) $summary['ad_cost'];
        $cogs = (float) collect($skuRows)->sum(fn (array $row): float => $row['net_quantity'] * $row['unit_cost']);
        $totalRevenue = (float) ($summary['settlement_revenue'] ?: ($skuRevenue ?: $summary['gmv']));
        $revenueAdjustment = $totalRevenue - $skuRevenue;
        $totalCost = $cogs + $marketplaceFees + $adCost;
        $profit = $totalRevenue - $totalCost;
        $completedOrderCount = (int) ($summary['completed_order_count'] ?? $summary['order_count']);
        $orderCount = max(1, $completedOrderCount);

        $skuSummaries = [];
        foreach ($skuRows as $row) {
            $share = $skuRevenue > 0 ? $row['revenue'] / $skuRevenue : 0;
            $allocatedRevenueAdjustment = $revenueAdjustment * $share;
            $finalRevenue = $row['revenue'] + $allocatedRevenueAdjustment;
            $allocatedFees = $marketplaceFees * $share;
            $allocatedAdCost = $adCost * $share;
            $rowCogs = $row['net_quantity'] * $row['unit_cost'];
            $rowProfit = $finalRevenue - $rowCogs - $allocatedFees - $allocatedAdCost;

            $skuSummaries[] = [
                'seller_sku' => $row['seller_sku'],
                'fob_sku' => $row['fob_sku'],
                'product_name' => $row['product_name'],
                'unit_cost' => $row['unit_cost'],
                'quantity_sold' => $row['quantity_sold'],
                'quantity_returned' => $row['quantity_returned'],
                'net_quantity' => $row['net_quantity'],
                'original_revenue' => $row['revenue'],
                'revenue' => $finalRevenue,
                'refund_amount' => $row['refund_amount'],
                'allocated_revenue_adjustment' => $allocatedRevenueAdjustment,
                'final_revenue' => $finalRevenue,
                'cogs' => $rowCogs,
                'allocated_fees' => $allocatedFees,
                'allocated_ad_cost' => $allocatedAdCost,
                'profit' => $rowProfit,
                'profit_per_unit' => $row['net_quantity'] > 0 ? $rowProfit / $row['net_quantity'] : 0,
                'status' => $rowProfit >= 0 ? 'profit' : 'loss',
            ];
        }

        return [
            'order_count' => $completedOrderCount,
            'item_count' => (int) $summary['item_count'],
            'gmv' => (float) $summary['gmv'],
            'settlement_revenue' => (float) $summary['settlement_revenue'],
            'sku_gross_revenue_total' => $skuGrossRevenue,
            'sku_refund_total' => $skuRefundTotal,
            'sku_revenue_total' => $skuRevenue,
            'revenue_adjustment' => $revenueAdjustment,
            'marketplace_fees' => $marketplaceFees,
            'ad_cost' => $adCost,
            'cogs' => $cogs,
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'profit' => $profit,
            'profit_per_order' => $profit / $orderCount,
            'ad_breakeven' => $totalRevenue - $marketplaceFees - $cogs,
            'completed_order_count' => $completedOrderCount,
            'analytics_order_count' => (int) ($summary['analytics_order_count'] ?? $summary['order_count']),
            'sku_summaries' => $skuSummaries,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFob(string $path): array
    {
        $rows = $this->reader->rows($path);
        $items = [];
        $byCandidate = [];
        $byNumber = [];

        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber < 7) {
                continue;
            }

            $code = trim((string) ($row[1] ?? ''));
            $description = trim((string) ($row[2] ?? ''));
            $cost = $this->number($row[20] ?? 0);
            $sku = trim((string) ($row[21] ?? $code));

            if ($sku === '' || $cost <= 0) {
                continue;
            }

            $item = [
                'code' => $code,
                'sku' => $sku,
                'description' => $description,
                'unit_cost' => (float) $cost,
            ];
            $items[] = $item;

            foreach (array_unique(array_merge($this->codeCandidates($sku), $this->codeCandidates($code))) as $candidate) {
                $byCandidate[$candidate] = $item;
            }

            $number = $this->lastNumber($sku ?: $code);
            if ($number !== '') {
                $byNumber[$number][] = $item;
            }
        }

        if ($items === []) {
            throw new RuntimeException('File FOB chưa có dòng mã hàng + giá vốn hợp lệ.');
        }

        return compact('items', 'byCandidate', 'byNumber');
    }

    /**
     * @return array<string, mixed>
     */
    private function parseAnalytics(string $path): array
    {
        $data = $this->parseTwoLineMetricFile($path);

        return [
            'gmv' => $this->number($data['gmv'] ?? 0),
            'items_sold' => $this->number($data['so mon ban ra'] ?? 0),
            'sku_orders' => $this->number($data['don hang sku'] ?? 0),
            'orders' => $this->number($data['don hang'] ?? 0),
            'customers' => $this->number($data['khach hang'] ?? 0),
            'visitors' => $this->number($data['khach truy cap'] ?? 0),
            'product_impressions' => $this->number($data['luot hien thi san pham'] ?? 0),
            'unique_product_impressions' => $this->number($data['luot hien thi san pham doc nhat'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseAds(string $path): array
    {
        $data = $this->parseTwoLineMetricFile($path);

        return [
            'ad_cost' => $this->number($data['chi phi quang cao'] ?? 0),
            'sku_order_count' => $this->number($data['so luong don hang sku'] ?? 0),
            'cost_per_order' => $this->number($data['chi phi moi don hang'] ?? 0),
            'gross_revenue' => $this->number($data['doanh thu gop'] ?? 0),
            'roi' => $this->number($data['roi'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAnalytics(): array
    {
        return [
            'gmv' => 0,
            'items_sold' => 0,
            'sku_orders' => 0,
            'orders' => 0,
            'customers' => 0,
            'visitors' => 0,
            'product_impressions' => 0,
            'unique_product_impressions' => 0,
            'source' => 'not_used',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function manualAds(float $costPerOrder, int $orderCount, float $adCost): array
    {
        return [
            'ad_cost' => $adCost,
            'sku_order_count' => $orderCount,
            'cost_per_order' => $costPerOrder,
            'gross_revenue' => 0,
            'roi' => 0,
            'source' => 'manual_cost_per_order',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseSettlement(string $path): array
    {
        $rows = $this->reader->rows($path, 'Báo cáo');
        $totals = [
            'period_start' => null,
            'period_end' => null,
            'settlement_amount' => 0.0,
            'total_revenue' => 0.0,
            'seller_subtotal_after_discount' => 0.0,
            'seller_subtotal_before_discount' => 0.0,
            'seller_discount' => 0.0,
            'total_fees' => 0.0,
        ];

        foreach ($rows as $row) {
            $texts = array_values($row);
            foreach ($texts as $index => $text) {
                $normalized = $this->normalize((string) $text);
                $nextValue = $this->firstNumericAfter($texts, $index + 1);

                if ($normalized === 'khoang thoi gian') {
                    [$start, $end] = $this->parsePeriodRange((string) ($texts[$index + 4] ?? $texts[$index + 1] ?? ''));
                    $totals['period_start'] = $start;
                    $totals['period_end'] = $end;
                } elseif ($normalized === 'tong so tien quyet toan') {
                    $totals['settlement_amount'] = (float) $nextValue;
                } elseif ($normalized === 'tong doanh thu') {
                    $totals['total_revenue'] = (float) $nextValue;
                } elseif ($normalized === 'tong phu sau giam gia cua nguoi ban') {
                    $totals['seller_subtotal_after_discount'] = (float) $nextValue;
                } elseif ($normalized === 'tong phu truoc giam gia') {
                    $totals['seller_subtotal_before_discount'] = (float) $nextValue;
                } elseif ($normalized === 'giam gia cua nguoi ban') {
                    $totals['seller_discount'] = (float) $nextValue;
                } elseif ($normalized === 'tong phi') {
                    $totals['total_fees'] = (float) $nextValue;
                }
            }
        }

        if ($totals['total_revenue'] <= 0) {
            throw new RuntimeException('File quyết toán chưa nhận diện được dòng Tổng doanh thu.');
        }

        $totals['orders_by_id'] = $this->parseSettlementOrderDetails($path);
        $totals['order_count'] = count($totals['orders_by_id']);
        $totals['order_revenue'] = (float) collect($totals['orders_by_id'])->sum('revenue');
        $totals['order_subtotal_after_discount'] = (float) collect($totals['orders_by_id'])->sum('subtotal_after_discount');
        $totals['order_refund_after_discount'] = (float) collect($totals['orders_by_id'])->sum('refund_after_discount');
        $positiveOrders = collect($totals['orders_by_id'])->filter(fn (array $order): bool => (float) $order['revenue'] > 0);
        $negativeOrders = collect($totals['orders_by_id'])->filter(fn (array $order): bool => (float) $order['revenue'] < 0);
        $zeroOrders = collect($totals['orders_by_id'])->filter(fn (array $order): bool => abs((float) $order['revenue']) < 0.01);
        $totals['positive_order_count'] = $positiveOrders->count();
        $totals['positive_order_revenue'] = (float) $positiveOrders->sum('revenue');
        $totals['negative_order_count'] = $negativeOrders->count();
        $totals['negative_order_revenue'] = (float) $negativeOrders->sum('revenue');
        $totals['zero_order_count'] = $zeroOrders->count();
        $totals['sample_positive_order_ids'] = $positiveOrders->keys()->take(10)->values()->all();
        $totals['sample_negative_order_ids'] = $negativeOrders->keys()->take(10)->values()->all();
        $createdDates = collect($totals['orders_by_id'])
            ->pluck('created_at')
            ->map(fn (string $date): ?string => $this->parseDate($date)?->toDateString())
            ->filter()
            ->values();
        $settledDates = collect($totals['orders_by_id'])
            ->pluck('settled_at')
            ->map(fn (string $date): ?string => $this->parseDate($date)?->toDateString())
            ->filter()
            ->values();
        $totals['order_created_start'] = $createdDates->isNotEmpty() ? $createdDates->min() : null;
        $totals['order_created_end'] = $createdDates->isNotEmpty() ? $createdDates->max() : null;
        $totals['order_settled_start'] = $settledDates->isNotEmpty() ? $settledDates->min() : null;
        $totals['order_settled_end'] = $settledDates->isNotEmpty() ? $settledDates->max() : null;

        return $totals;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function parseSettlementOrderDetails(string $path): array
    {
        $orders = [];
        $columns = [];
        $requiredColumns = ['Loại giao dịch', 'ID đơn hàng liên quan', 'Tổng doanh thu', 'Tổng phụ sau giảm giá của người bán', 'Tổng phụ của khoản hoàn tiền sau giảm giá của người bán'];

        $this->reader->eachRow($path, function (array $row, int $rowNumber) use (&$orders, &$columns, $requiredColumns): void {
            if ($rowNumber === 1) {
                $columns = $this->mapHeaderColumns($row);
                foreach ($requiredColumns as $required) {
                    if (! isset($columns[$required])) {
                        throw new RuntimeException('File quyết toán thiếu cột '.$required.' trong sheet Chi tiết đơn hàng.');
                    }
                }

                return;
            }

            if ($rowNumber <= 1) {
                return;
            }

            if (trim((string) ($row[$columns['Loại giao dịch']] ?? '')) !== 'Đơn hàng') {
                return;
            }

            $orderId = trim((string) ($row[$columns['ID đơn hàng liên quan']] ?? ''));
            if ($orderId === '' || $orderId === '/') {
                return;
            }

            if (! isset($orders[$orderId])) {
                $orders[$orderId] = [
                    'order_id' => $orderId,
                    'rows' => 0,
                    'revenue' => 0.0,
                    'subtotal_after_discount' => 0.0,
                    'refund_after_discount' => 0.0,
                    'settlement_amount' => 0.0,
                    'fees' => 0.0,
                    'created_at' => trim((string) ($row[$columns['Thời gian tạo đơn hàng'] ?? 0] ?? '')),
                    'settled_at' => trim((string) ($row[$columns['Thời gian quyết toán đơn hàng'] ?? 0] ?? '')),
                ];
            }

            $orders[$orderId]['rows']++;
            $orders[$orderId]['revenue'] += $this->number($row[$columns['Tổng doanh thu']] ?? 0);
            $orders[$orderId]['subtotal_after_discount'] += $this->number($row[$columns['Tổng phụ sau giảm giá của người bán']] ?? 0);
            $orders[$orderId]['refund_after_discount'] += $this->number($row[$columns['Tổng phụ của khoản hoàn tiền sau giảm giá của người bán']] ?? 0);
            $orders[$orderId]['settlement_amount'] += $this->number($row[$columns['Tổng số tiền quyết toán'] ?? 0] ?? 0);
            $orders[$orderId]['fees'] += $this->number($row[$columns['Tổng phí'] ?? 0] ?? 0);
        }, 'Chi tiết đơn hàng');

        if ($orders === []) {
            throw new RuntimeException('File quyết toán chưa có danh sách ID đơn hàng trong sheet Chi tiết đơn hàng.');
        }

        return $orders;
    }

    /**
     * @param array<string, array<string, mixed>> $settlementOrders
     * @return array<string, mixed>
     */
    private function parseOrderSkuList(string $path, array $settlementOrders = []): array
    {
        $columns = [];
        $requiredColumns = ['Order ID', 'Order Status', 'Seller SKU', 'Quantity', 'Sku Quantity of return', 'SKU Subtotal After Discount'];
        $skuRows = [];
        $uniqueOrders = [];
        $fileUniqueOrders = [];
        $statusCounts = [];
        $dates = [];
        $rowCount = 0;
        $matchedSettlementOrders = [];

        $this->reader->eachRow($path, function (array $row, int $rowNumber) use (&$columns, $requiredColumns, &$skuRows, &$uniqueOrders, &$fileUniqueOrders, &$statusCounts, &$dates, &$rowCount, &$matchedSettlementOrders, $settlementOrders): void {
            if ($rowNumber === 1) {
                $columns = $this->mapHeaderColumns($row);
                foreach ($requiredColumns as $required) {
                    if (! isset($columns[$required])) {
                        throw new RuntimeException('File đơn hàng/SKU thiếu cột '.$required.'.');
                    }
                }

                return;
            }

            if ($rowNumber <= 2) {
                return;
            }

            $sellerSku = trim((string) ($row[$columns['Seller SKU']] ?? ''));
            if ($sellerSku === '') {
                return;
            }

            $orderId = trim((string) ($row[$columns['Order ID']] ?? ''));
            if ($orderId !== '') {
                $fileUniqueOrders[$orderId] = true;
            }

            $status = trim((string) ($row[$columns['Order Status']] ?? ''));
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if ($settlementOrders !== [] && ($orderId === '' || ! isset($settlementOrders[$orderId]))) {
                return;
            }

            if ($orderId !== '') {
                $matchedSettlementOrders[$orderId] = true;
            }

            if ($this->normalize($status) === 'da huy') {
                return;
            }

            $rowCount++;
            if ($orderId !== '') {
                $uniqueOrders[$orderId] = true;
            }

            $created = $this->parseDate((string) ($row[$columns['Created Time'] ?? 0] ?? ''));
            if ($created) {
                $dates[] = $created->toDateString();
            }

            $quantity = (float) $this->number($row[$columns['Quantity']] ?? 0);
            $returned = (float) $this->number($row[$columns['Sku Quantity of return']] ?? 0);
            $afterDiscount = (float) $this->number($row[$columns['SKU Subtotal After Discount']] ?? 0);
            $netQuantity = max(0, $quantity - $returned);
            $revenue = $quantity > 0 ? $afterDiscount * ($netQuantity / $quantity) : 0;
            $refund = max(0, $afterDiscount - $revenue);

            if (! isset($skuRows[$sellerSku])) {
                $skuRows[$sellerSku] = [
                    'key' => md5($sellerSku),
                    'seller_sku' => $sellerSku,
                    'product_name' => trim((string) ($row[$columns['Product Name'] ?? 0] ?? '')),
                    'quantity_sold' => 0.0,
                    'quantity_returned' => 0.0,
                    'net_quantity' => 0.0,
                    'revenue' => 0.0,
                    'refund_amount' => 0.0,
                ];
            }

            $skuRows[$sellerSku]['quantity_sold'] += $quantity;
            $skuRows[$sellerSku]['quantity_returned'] += $returned;
            $skuRows[$sellerSku]['net_quantity'] += $netQuantity;
            $skuRows[$sellerSku]['revenue'] += $revenue;
            $skuRows[$sellerSku]['refund_amount'] += $refund;
        }, 'OrderSKUList');

        $missingSettlementOrders = array_diff_key($settlementOrders, $matchedSettlementOrders);
        $missingRevenue = (float) collect($missingSettlementOrders)->sum('revenue');
        $missingSubtotal = (float) collect($missingSettlementOrders)->sum('subtotal_after_discount');
        $missingRefund = (float) collect($missingSettlementOrders)->sum('refund_after_discount');

        return [
            'row_count' => $rowCount,
            'unique_orders' => count($uniqueOrders),
            'file_unique_orders' => count($fileUniqueOrders),
            'status_counts' => $statusCounts,
            'settlement_filter' => [
                'enabled' => $settlementOrders !== [],
                'settlement_order_count' => count($settlementOrders),
                'matched_order_count' => count($matchedSettlementOrders),
                'missing_order_count' => count($missingSettlementOrders),
                'missing_revenue' => $missingRevenue,
                'missing_subtotal_after_discount' => $missingSubtotal,
                'missing_refund_after_discount' => $missingRefund,
                'missing_net_subtotal_after_discount' => $missingSubtotal + $missingRefund,
                'sample_missing_order_ids' => array_slice(array_keys($missingSettlementOrders), 0, 10),
            ],
            'period_start' => $dates !== [] ? min($dates) : null,
            'period_end' => $dates !== [] ? max($dates) : null,
            'sku_rows' => $skuRows,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseTwoLineMetricFile(string $path): array
    {
        $rows = $this->reader->rows($path);
        $header = $rows[2] ?? [];
        $values = $rows[3] ?? [];
        $data = [];

        foreach ($header as $column => $label) {
            $key = $this->normalize((string) $label);
            if ($key !== '') {
                $data[$key] = (string) ($values[$column] ?? '');
            }
        }

        return $data;
    }

    /**
     * @param array<int, string> $header
     * @return array<string, int>
     */
    private function mapHeaderColumns(array $header): array
    {
        $columns = [];
        foreach ($header as $column => $label) {
            $label = trim((string) $label);
            if ($label !== '') {
                $columns[$label] = $column;
            }
        }

        return $columns;
    }

    /**
     * @param array<string, mixed> $fob
     * @return array<string, mixed>
     */
    private function suggestSkuMap(string $sellerSku, string $productName, array $fob): array
    {
        foreach ($this->codeCandidates($sellerSku) as $candidate) {
            if (isset($fob['byCandidate'][$candidate])) {
                return $this->mapSuggestion($fob['byCandidate'][$candidate], 'HIGH', 'Khớp mã SKU');
            }
        }

        $number = $this->lastNumber($sellerSku);
        if ($number !== '' && count($fob['byNumber'][$number] ?? []) === 1) {
            return $this->mapSuggestion($fob['byNumber'][$number][0], 'MEDIUM', 'Khớp số mã duy nhất');
        }

        return [
            'fob_sku' => null,
            'fob_code' => null,
            'unit_cost' => 0,
            'source' => 'manual',
            'confidence' => 'MANUAL',
            'reason' => 'Chưa có FOB hoặc dễ nhầm',
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function mapSuggestion(array $item, string $confidence, string $reason): array
    {
        return [
            'fob_sku' => $item['sku'],
            'fob_code' => $item['code'],
            'unit_cost' => $item['unit_cost'],
            'source' => $confidence === 'HIGH' ? 'auto' : 'manual',
            'confidence' => $confidence,
            'reason' => $reason,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function codeCandidates(string $value): array
    {
        $compact = $this->compact($value);
        $candidates = [$compact];

        if (preg_match_all('/(QD|QL|CV|SM|QG|QN|QS|AO)([A-Z]*)(\d+)/', $compact, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $candidates[] = $match[1].$match[3];
            }
        }

        if (str_contains($compact, 'QUANGIO') && str_contains($compact, '101')) {
            $candidates[] = 'QG101';
        }
        if ((str_contains($compact, 'BABYDOL') || str_contains($compact, 'BBABYDOL') || str_contains($compact, 'BAYDOL')) && str_contains($compact, '79')) {
            $candidates[] = 'SM79';
        }
        if (str_contains($compact, 'QDCAPCHUN') && str_contains($compact, '96')) {
            $candidates[] = 'QD96';
        }
        if (str_contains($compact, 'CVTHUN') && str_contains($compact, '130')) {
            $candidates[] = 'CV130';
        }
        if (str_contains($compact, 'NI') && str_contains($compact, '135')) {
            $candidates[] = 'QN135';
        }
        if (str_contains($compact, 'QL81')) {
            $candidates[] = 'QL81';
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function lastNumber(string $value): string
    {
        preg_match_all('/\d+/', $this->compact($value), $matches);

        return $matches[0] !== [] ? end($matches[0]) : '';
    }

    private function compact(string $value): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', Str::ascii(mb_strtoupper($value))) ?? '';
    }

    private function normalize(string $value): string
    {
        $value = Str::ascii(mb_strtolower(trim($value)));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function number(mixed $value): float
    {
        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        $text = preg_replace('/[^\d,\.\-]/', '', $text) ?? '';
        if ($text === '' || $text === '-') {
            return 0.0;
        }

        $commaCount = substr_count($text, ',');
        $dotCount = substr_count($text, '.');

        if ($commaCount > 0 && $dotCount > 0) {
            $lastComma = strrpos($text, ',');
            $lastDot = strrpos($text, '.');
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
            $decimalLength = strlen($text) - max($lastComma, $lastDot) - 1;

            if ($decimalLength === 3) {
                $text = str_replace([',', '.'], '', $text);
            } else {
                $text = str_replace($thousandSeparator, '', $text);
                $text = str_replace($decimalSeparator, '.', $text);
            }
        } elseif ($commaCount > 0) {
            $text = $this->normalizeSingleSeparatorNumber($text, ',');
        } elseif ($dotCount > 0) {
            $text = $this->normalizeSingleSeparatorNumber($text, '.');
        }

        return is_numeric($text) ? (float) $text : 0.0;
    }

    private function normalizeSingleSeparatorNumber(string $text, string $separator): string
    {
        $parts = explode($separator, $text);

        if (count($parts) > 2) {
            $validThousands = collect(array_slice($parts, 1))->every(fn (string $part): bool => strlen($part) === 3);

            return $validThousands ? implode('', $parts) : str_replace($separator, '', $text);
        }

        $decimalLength = strlen($parts[1] ?? '');
        if ($decimalLength === 3) {
            return implode('', $parts);
        }

        return $separator === ',' ? str_replace(',', '.', $text) : $text;
    }

    private function firstNumericAfter(array $values, int $offset): float
    {
        for ($i = $offset; $i < count($values); $i++) {
            $value = $this->number($values[$i] ?? '');
            if ($value !== 0.0 || trim((string) ($values[$i] ?? '')) === '0') {
                return $value;
            }
        }

        return 0.0;
    }

    /**
     * @return array{0:?string, 1:?string}
     */
    private function parsePeriodRange(string $value): array
    {
        if (preg_match('/(\d{4})[\/\-](\d{2})[\/\-](\d{2}).*?(\d{4})[\/\-](\d{2})[\/\-](\d{2})/', $value, $matches)) {
            return [
                "{$matches[1]}-{$matches[2]}-{$matches[3]}",
                "{$matches[4]}-{$matches[5]}-{$matches[6]}",
            ];
        }

        return [null, null];
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y H:i:s', 'Y/m/d H:i:s', 'd/m/Y', 'Y/m/d', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                //
            }
        }

        return null;
    }
}
