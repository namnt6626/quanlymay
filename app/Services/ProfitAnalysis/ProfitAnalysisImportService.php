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
    public function preview(array $files, Carbon $periodMonth): array
    {
        $fob = isset($files['fob_file']) ? $this->parseFob($files['fob_file']) : [
            'items' => [],
            'byCandidate' => [],
            'byNumber' => [],
        ];
        $analytics = $this->parseAnalytics($files['analytics_file']);
        $ads = $this->parseAds($files['ad_file']);
        $settlement = $this->parseSettlement($files['settlement_file']);
        $orders = $this->parseOrderSkuList($files['order_file']);

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
                'settlement' => $settlement,
                'orders' => [
                    'row_count' => $orders['row_count'],
                    'unique_orders' => $orders['unique_orders'],
                    'sku_count' => count($skuRows),
                    'status_counts' => $orders['status_counts'],
                ],
                'fob' => [
                    'sku_count' => count($fob['items']),
                ],
            ],
            'summary' => [
                'sku_count' => count($skuRows),
                'missing_cost_count' => collect($skuRows)->where('needs_cost', true)->count(),
                'auto_mapped_count' => collect($skuRows)->whereIn('confidence', ['HIGH', 'SAVED'])->where('needs_cost', false)->count(),
                'order_count' => (int) ($analytics['orders'] ?: $orders['unique_orders']),
                'item_count' => (int) ($analytics['items_sold'] ?: collect($skuRows)->sum('quantity_sold')),
                'gmv' => (float) $analytics['gmv'],
                'settlement_revenue' => (float) $settlement['total_revenue'],
                'marketplace_fees' => abs((float) $settlement['total_fees']),
                'ad_cost' => (float) $ads['ad_cost'],
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
                'marketplace_fees' => $totals['marketplace_fees'],
                'ad_cost' => $totals['ad_cost'],
                'cogs' => $totals['cogs'],
                'total_revenue' => $totals['total_revenue'],
                'total_cost' => $totals['total_cost'],
                'profit' => $totals['profit'],
                'profit_per_order' => $totals['profit_per_order'],
                'ad_breakeven' => $totals['ad_breakeven'],
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
        $marketplaceFees = (float) $summary['marketplace_fees'];
        $adCost = (float) $summary['ad_cost'];
        $cogs = (float) collect($skuRows)->sum(fn (array $row): float => $row['net_quantity'] * $row['unit_cost']);
        $totalRevenue = (float) ($summary['settlement_revenue'] ?: $summary['gmv']);
        $totalCost = $cogs + $marketplaceFees + $adCost;
        $profit = $totalRevenue - $totalCost;
        $orderCount = max(1, (int) $summary['order_count']);

        $skuSummaries = [];
        foreach ($skuRows as $row) {
            $share = $skuRevenue > 0 ? $row['revenue'] / $skuRevenue : 0;
            $allocatedFees = $marketplaceFees * $share;
            $allocatedAdCost = $adCost * $share;
            $rowCogs = $row['net_quantity'] * $row['unit_cost'];
            $rowProfit = $row['revenue'] - $rowCogs - $allocatedFees - $allocatedAdCost;

            $skuSummaries[] = [
                'seller_sku' => $row['seller_sku'],
                'fob_sku' => $row['fob_sku'],
                'product_name' => $row['product_name'],
                'unit_cost' => $row['unit_cost'],
                'quantity_sold' => $row['quantity_sold'],
                'quantity_returned' => $row['quantity_returned'],
                'net_quantity' => $row['net_quantity'],
                'revenue' => $row['revenue'],
                'refund_amount' => $row['refund_amount'],
                'cogs' => $rowCogs,
                'allocated_fees' => $allocatedFees,
                'allocated_ad_cost' => $allocatedAdCost,
                'profit' => $rowProfit,
                'profit_per_unit' => $row['net_quantity'] > 0 ? $rowProfit / $row['net_quantity'] : 0,
                'status' => $rowProfit >= 0 ? 'profit' : 'loss',
            ];
        }

        return [
            'order_count' => $orderCount,
            'item_count' => (int) $summary['item_count'],
            'gmv' => (float) $summary['gmv'],
            'settlement_revenue' => (float) $summary['settlement_revenue'],
            'marketplace_fees' => $marketplaceFees,
            'ad_cost' => $adCost,
            'cogs' => $cogs,
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'profit' => $profit,
            'profit_per_order' => $profit / $orderCount,
            'ad_breakeven' => $totalRevenue - $marketplaceFees - $cogs,
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

        return $totals;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseOrderSkuList(string $path): array
    {
        $rows = $this->reader->rows($path, 'OrderSKUList');
        $header = $rows[1] ?? [];
        $columns = $this->mapHeaderColumns($header);

        foreach (['Order ID', 'Order Status', 'Seller SKU', 'Quantity', 'Sku Quantity of return', 'SKU Subtotal After Discount'] as $required) {
            if (! isset($columns[$required])) {
                throw new RuntimeException('File đơn hàng/SKU thiếu cột '.$required.'.');
            }
        }

        $skuRows = [];
        $uniqueOrders = [];
        $statusCounts = [];
        $dates = [];
        $rowCount = 0;

        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber <= 2) {
                continue;
            }

            $sellerSku = trim((string) ($row[$columns['Seller SKU']] ?? ''));
            if ($sellerSku === '') {
                continue;
            }

            $status = trim((string) ($row[$columns['Order Status']] ?? ''));
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            if ($this->normalize($status) === 'da huy') {
                continue;
            }

            $rowCount++;
            $orderId = trim((string) ($row[$columns['Order ID']] ?? ''));
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
        }

        return [
            'row_count' => $rowCount,
            'unique_orders' => count($uniqueOrders),
            'status_counts' => $statusCounts,
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
