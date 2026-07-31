<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Models\ProfitAnalysisPeriod;
use App\Models\ProfitAnalysisShop;
use App\Services\ProfitAnalysis\ProfitAnalysisImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class ProfitAnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = in_array($request->input('tab'), ['total', 'shopee', 'tiktok'], true)
            ? (string) $request->input('tab')
            : 'total';

        $shops = ProfitAnalysisShop::query()
            ->orderBy('marketplace')
            ->orderBy('name')
            ->get();
        $availableShopIds = $activeTab === 'total'
            ? $shops->pluck('id')->all()
            : $shops->where('marketplace', $activeTab)->pluck('id')->all();
        $selectedShopId = $request->filled('shop_id') ? $request->integer('shop_id') : null;
        if ($selectedShopId && ! in_array($selectedShopId, $availableShopIds, true)) {
            $selectedShopId = null;
        }

        $periods = ProfitAnalysisPeriod::query()
            ->with(['confirmedBy', 'shop'])
            ->when($activeTab !== 'total', fn ($query) => $query->where('marketplace', $activeTab))
            ->when($selectedShopId, fn ($query) => $query->where('shop_id', $selectedShopId))
            ->orderByDesc('period_month')
            ->orderBy('marketplace')
            ->paginate(paginationPerPage())
            ->withQueryString();

        $monthOptions = ProfitAnalysisPeriod::query()
            ->select('period_month')
            ->distinct()
            ->orderByDesc('period_month')
            ->pluck('period_month')
            ->mapWithKeys(fn ($month) => [
                Carbon::parse($month)->format('Y-m') => 'T'.Carbon::parse($month)->format('n/Y'),
            ])
            ->all();

        $selectedMonth = $request->input('month');
        if (! is_string($selectedMonth) || ! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = array_key_first($monthOptions);
        }

        $selectedPeriod = $selectedMonth ? $this->totalPeriod($selectedMonth, null, $selectedShopId) : null;
        $shopeePeriod = $selectedMonth ? $this->totalPeriod($selectedMonth, 'shopee', $selectedShopId) : null;
        $tiktokPeriod = $selectedMonth ? $this->totalPeriod($selectedMonth, 'tiktok', $selectedShopId) : null;

        $selectedPeriod ??= ProfitAnalysisPeriod::query()
            ->with(['shop', 'skuSummaries' => fn ($query) => $query->orderBy('profit')->orderByDesc('net_quantity')])
            ->orderByDesc('period_month')
            ->first();

        return view('content.san-xuat.phan-tich-lai-lo.index', [
            'periods' => $periods,
            'monthOptions' => $monthOptions,
            'selectedMonth' => $selectedMonth,
            'selectedPeriod' => $selectedPeriod,
            'shopeePeriod' => $shopeePeriod,
            'tiktokPeriod' => $tiktokPeriod,
            'activeTab' => $activeTab,
            'shops' => $shops,
            'selectedShopId' => $selectedShopId,
        ]);
    }

    public function create(Request $request, ?string $marketplace = null): View|RedirectResponse
    {
        $marketplace = $this->normalizeMarketplace($marketplace ?? (string) $request->input('marketplace', 'tiktok'));
        if (! in_array($marketplace, ['tiktok', 'shopee'], true)) {
            return redirect()->route('phan-tich-lai-lo.index')->withErrors(['files' => 'Nền tảng nhập dữ liệu không hợp lệ.']);
        }

        $importToken = $this->importToken($request->old('import_token'));
        $shops = ProfitAnalysisShop::query()
            ->where('marketplace', $marketplace)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('content.san-xuat.phan-tich-lai-lo.create', [
            'months' => $this->monthOptions(),
            'importToken' => $importToken,
            'uploadedFiles' => Session::get($this->uploadSessionKey($importToken), []),
            'marketplace' => $marketplace,
            'marketplaceLabel' => $this->marketplaceLabel($marketplace),
            'shops' => $shops,
        ]);
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'import_token' => ['required', 'string', 'max:80'],
            'marketplace' => ['nullable', 'string', 'in:tiktok,shopee'],
            'file_key' => ['required', 'string', 'in:fob_file,settlement_file,order_file'],
            'upload_id' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+$/'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:10000'],
            'original_name' => ['required', 'string', 'max:255'],
            'reset_existing' => ['nullable', 'boolean'],
            'chunk' => ['required', 'file'],
        ], [
            'upload_id.regex' => 'Mã tải lên không hợp lệ.',
        ]);

        if (! Str::endsWith(Str::lower($validated['original_name']), '.xlsx')) {
            return response()->json(['message' => 'Chỉ hỗ trợ file có đuôi .xlsx.'], 422);
        }

        $token = $validated['import_token'];
        $marketplace = $this->normalizeMarketplace((string) ($validated['marketplace'] ?? 'tiktok'));
        $fileKey = $validated['file_key'];
        $uploadId = $validated['upload_id'];
        $chunkIndex = (int) $validated['chunk_index'];
        $totalChunks = (int) $validated['total_chunks'];
        $baseDirectory = storage_path('app/profit-analysis-imports/'.$token);
        $chunkDirectory = $baseDirectory.'/chunks/'.$uploadId;
        File::ensureDirectoryExists($chunkDirectory);

        $request->file('chunk')->move($chunkDirectory, $chunkIndex.'.part');

        if ($this->uploadedChunkCount($chunkDirectory) < $totalChunks) {
            return response()->json([
                'complete' => false,
                'uploaded_chunks' => $this->uploadedChunkCount($chunkDirectory),
                'total_chunks' => $totalChunks,
            ]);
        }

        $directory = $baseDirectory.'/files';
        File::ensureDirectoryExists($directory);
        $filename = $fileKey.'_'.Str::uuid().'.xlsx';
        $path = $directory.'/'.$filename;

        $output = fopen($path, 'wb');
        if ($output === false) {
            return response()->json(['message' => 'Không tạo được file tạm trên server.'], 500);
        }

        try {
            for ($index = 0; $index < $totalChunks; $index++) {
                $chunkPath = $chunkDirectory.'/'.$index.'.part';
                if (! is_file($chunkPath)) {
                    fclose($output);
                    @unlink($path);

                    return response()->json(['message' => 'Thiếu mảnh upload, vui lòng chọn lại file.'], 422);
                }

                $input = fopen($chunkPath, 'rb');
                if ($input === false) {
                    fclose($output);
                    @unlink($path);

                    return response()->json(['message' => 'Không đọc được mảnh upload.'], 500);
                }
                stream_copy_to_stream($input, $output);
                fclose($input);
            }
        } finally {
            if (is_resource($output)) {
                fclose($output);
            }
        }

        File::deleteDirectory($chunkDirectory);

        $sessionKey = $this->uploadSessionKey($token);
        $uploads = Session::get($sessionKey, []);
        $allowsMultiple = $marketplace === 'shopee' && $fileKey === 'order_file';
        $resetExisting = $allowsMultiple && (bool) ($validated['reset_existing'] ?? false);

        if ($resetExisting && isset($uploads[$fileKey]['files']) && is_array($uploads[$fileKey]['files'])) {
            foreach ($uploads[$fileKey]['files'] as $existingFile) {
                if (isset($existingFile['path']) && is_file($existingFile['path'])) {
                    @unlink($existingFile['path']);
                }
            }
            unset($uploads[$fileKey]);
        }

        if (! $allowsMultiple && isset($uploads[$fileKey]['path']) && is_file($uploads[$fileKey]['path'])) {
            @unlink($uploads[$fileKey]['path']);
        }

        $filePayload = [
            'path' => $path,
            'name' => $validated['original_name'],
            'size' => filesize($path) ?: 0,
            'uploaded_at' => now()->toDateTimeString(),
        ];

        if ($allowsMultiple) {
            $existingFiles = $uploads[$fileKey]['files'] ?? [];
            if (isset($uploads[$fileKey]['path'])) {
                $existingFiles[] = $uploads[$fileKey];
            }
            $existingFiles[] = $filePayload;
            $uploads[$fileKey] = [
                'files' => array_values($existingFiles),
                'name' => count($existingFiles).' file đơn hàng Shopee',
                'size' => array_sum(array_map(fn (array $file): int => (int) ($file['size'] ?? 0), $existingFiles)),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        } else {
            $uploads[$fileKey] = $filePayload;
        }
        Session::put($sessionKey, $uploads);

        return response()->json([
            'complete' => true,
            'message' => 'Đã tải lên '.$uploads[$fileKey]['name'],
            'file' => $uploads[$fileKey],
            'files' => $uploads[$fileKey]['files'] ?? null,
        ]);
    }

    public function preview(Request $request, ProfitAnalysisImportService $service): View|RedirectResponse
    {
        @ini_set('memory_limit', '512M');

        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'import_token' => ['required', 'string', 'max:80'],
            'ad_cost_per_order' => ['required', 'string', 'max:50'],
            'marketplace' => ['required', 'string', 'in:tiktok,shopee'],
            'shop_id' => ['nullable', 'string', 'max:50'],
            'new_shop_name' => ['nullable', 'string', 'max:255'],
        ], [
            'period_month.required' => 'Vui lòng chọn tháng phân tích.',
            'ad_cost_per_order.required' => 'Vui lòng nhập chi phí QC mỗi đơn hàng.',
        ]);

        $adCostPerOrder = $this->number($validated['ad_cost_per_order']);
        if ($adCostPerOrder < 0) {
            return back()->withInput()->withErrors(['ad_cost_per_order' => 'Chi phí QC mỗi đơn hàng không được âm.']);
        }

        $shop = $this->resolveImportShop($validated['marketplace'], $validated['shop_id'] ?? null, $validated['new_shop_name'] ?? null);

        $uploads = Session::get($this->uploadSessionKey($validated['import_token']), []);
        $missing = [];
        foreach (['fob_file', 'settlement_file', 'order_file'] as $requiredKey) {
            if (! $this->uploadedInputExists($uploads[$requiredKey] ?? null)) {
                $missing[] = $requiredKey;
            }
        }
        if ($missing !== []) {
            return back()->withInput()->withErrors(['files' => 'Vui lòng tải lên đủ 3 file bắt buộc trước khi kiểm tra dữ liệu.']);
        }

        $files = [];
        foreach ($uploads as $key => $file) {
            if (isset($file['files']) && is_array($file['files'])) {
                $paths = collect($file['files'])
                    ->pluck('path')
                    ->filter(fn ($path): bool => is_string($path) && is_file($path))
                    ->values()
                    ->all();
                if ($paths !== []) {
                    $files[$key] = $paths;
                }
            } elseif (isset($file['path']) && is_file($file['path'])) {
                $files[$key] = $file['path'];
            }
        }

        try {
            $preview = $service->preview(
                $files,
                $this->monthCarbon($validated['period_month']),
                $adCostPerOrder,
                $validated['marketplace'],
                $shop->id
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['files' => $exception->getMessage()]);
        }

        $preview['import_token'] = $validated['import_token'];
        $previewKey = 'profit_analysis_preview_'.str()->uuid()->toString();
        Session::put($previewKey, $preview);

        return view('content.san-xuat.phan-tich-lai-lo.preview', [
            'previewKey' => $previewKey,
            'preview' => $preview,
        ]);
    }

    public function storeShop(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marketplace' => ['required', 'string', 'in:tiktok,shopee'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $shop = $this->findOrCreateShop($validated['marketplace'], $validated['name']);

        return back()->with('success', 'Đã tạo shop '.$shop->name.'.');
    }

    public function updateShop(Request $request, ProfitAnalysisShop $profitAnalysisShop): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $normalized = $this->normalizeShopName($validated['name']);
        $exists = ProfitAnalysisShop::query()
            ->where('marketplace', $profitAnalysisShop->marketplace)
            ->where('normalized_name', $normalized)
            ->whereKeyNot($profitAnalysisShop->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['shop' => 'Shop này đã tồn tại trong '.$profitAnalysisShop->marketplace_label.'.']);
        }

        $profitAnalysisShop->update([
            'name' => trim($validated['name']),
            'normalized_name' => $normalized,
        ]);

        return back()->with('success', 'Đã đổi tên shop.');
    }

    public function toggleShop(ProfitAnalysisShop $profitAnalysisShop): RedirectResponse
    {
        $profitAnalysisShop->update(['is_active' => ! $profitAnalysisShop->is_active]);

        return back()->with('success', 'Đã cập nhật trạng thái shop.');
    }

    public function commit(Request $request, ProfitAnalysisImportService $service): View|RedirectResponse
    {
        $validated = $request->validate([
            'preview_key' => ['required', 'string'],
            'sku_maps' => ['required', 'array'],
            'sku_maps.*.unit_cost' => ['nullable', 'string', 'max:50'],
        ]);

        $preview = Session::get($validated['preview_key']);
        if (! is_array($preview)) {
            return redirect()->route('phan-tich-lai-lo.create')->withErrors([
                'files' => 'Phiên preview đã hết hạn, vui lòng upload lại.',
            ]);
        }

        try {
            $period = $service->commit($preview, $validated['sku_maps'], (int) $request->user()->id);
        } catch (RuntimeException $exception) {
            return view('content.san-xuat.phan-tich-lai-lo.preview', [
                'previewKey' => $validated['preview_key'],
                'preview' => $preview,
                'commitError' => $exception->getMessage(),
            ]);
        }

        Session::forget($validated['preview_key']);
        $this->clearTempUploads((string) ($preview['import_token'] ?? ''));

        return redirect()
            ->route('phan-tich-lai-lo.index', [
                'month' => $period->period_month->format('Y-m'),
                'tab' => $period->marketplace,
                'shop_id' => $period->shop_id,
            ])
            ->with('success', 'Đã cập nhật dữ liệu '.$period->label.' thành công.');
    }

    public function edit(ProfitAnalysisPeriod $profitAnalysisPeriod): View
    {
        $profitAnalysisPeriod->load(['skuSummaries' => fn ($query) => $query->orderByDesc('net_quantity')]);

        return view('content.san-xuat.phan-tich-lai-lo.edit', [
            'period' => $profitAnalysisPeriod,
        ]);
    }

    public function update(Request $request, ProfitAnalysisPeriod $profitAnalysisPeriod): RedirectResponse
    {
        $validated = $request->validate([
            'sku_costs' => ['required', 'array'],
            'sku_costs.*' => ['nullable', 'string', 'max:50'],
        ]);

        $profitAnalysisPeriod->load('skuSummaries');

        $totalRevenue = (float) $profitAnalysisPeriod->total_revenue;
        $marketplaceFees = (float) $profitAnalysisPeriod->marketplace_fees;
        $adCost = (float) $profitAnalysisPeriod->ad_cost;
        $orderCount = max(1, (int) ($profitAnalysisPeriod->completed_order_count ?: $profitAnalysisPeriod->order_count));
        $skuRevenue = max(0.01, (float) $profitAnalysisPeriod->skuSummaries->sum(function ($summary): float {
            return (float) ($summary->original_revenue ?: $summary->revenue);
        }));
        $revenueAdjustment = $totalRevenue - $skuRevenue;
        $totalCogs = 0.0;

        foreach ($profitAnalysisPeriod->skuSummaries as $summary) {
            $unitCost = $this->number($validated['sku_costs'][$summary->id] ?? $summary->unit_cost);
            $cogs = (float) $summary->net_quantity * $unitCost;
            $originalRevenue = (float) ($summary->original_revenue ?: $summary->revenue);
            $share = $originalRevenue / $skuRevenue;
            $allocatedRevenueAdjustment = $revenueAdjustment * $share;
            $finalRevenue = $originalRevenue + $allocatedRevenueAdjustment;
            $allocatedFees = $marketplaceFees * $share;
            $allocatedAdCost = $adCost * $share;
            $profit = $finalRevenue - $cogs - $allocatedFees - $allocatedAdCost;

            $summary->update([
                'unit_cost' => $unitCost,
                'original_revenue' => $originalRevenue,
                'revenue' => $finalRevenue,
                'allocated_revenue_adjustment' => $allocatedRevenueAdjustment,
                'final_revenue' => $finalRevenue,
                'cogs' => $cogs,
                'allocated_fees' => $allocatedFees,
                'allocated_ad_cost' => $allocatedAdCost,
                'profit' => $profit,
                'profit_per_unit' => (float) $summary->net_quantity > 0 ? $profit / (float) $summary->net_quantity : 0,
                'status' => $profit >= 0 ? 'profit' : 'loss',
            ]);

            $totalCogs += $cogs;
        }

        $totalCost = $totalCogs + $marketplaceFees + $adCost;
        $profit = $totalRevenue - $totalCost;

        $profitAnalysisPeriod->update([
            'sku_revenue_total' => $skuRevenue,
            'revenue_adjustment' => $revenueAdjustment,
            'cogs' => $totalCogs,
            'total_cost' => $totalCost,
            'profit' => $profit,
            'profit_per_order' => $profit / $orderCount,
            'ad_breakeven' => $totalRevenue - $marketplaceFees - $totalCogs,
            'confirmed_by' => (int) $request->user()->id,
            'confirmed_at' => now(),
        ]);

        return redirect()
            ->route('phan-tich-lai-lo.index', [
                'month' => $profitAnalysisPeriod->period_month->format('Y-m'),
                'tab' => $profitAnalysisPeriod->marketplace,
                'shop_id' => $profitAnalysisPeriod->shop_id,
            ])
            ->with('success', 'Đã cập nhật dữ liệu '.$profitAnalysisPeriod->label.'.');
    }

    public function destroy(ProfitAnalysisPeriod $profitAnalysisPeriod): RedirectResponse
    {
        $label = $profitAnalysisPeriod->label;
        $profitAnalysisPeriod->delete();

        return redirect()
            ->route('phan-tich-lai-lo.index')
            ->with('success', 'Đã xóa dữ liệu '.$label.'.');
    }

    /**
     * @return array<string, string>
     */
    private function monthOptions(): array
    {
        $months = [];
        $start = now()->copy()->subMonths(18)->startOfMonth();
        $end = now()->copy()->addMonth()->startOfMonth();

        while ($start <= $end) {
            $months[$start->format('Y-m')] = 'T'.$start->format('n/Y');
            $start->addMonth();
        }

        return array_reverse($months, true);
    }

    private function importToken(?string $reuseToken = null): string
    {
        if (is_string($reuseToken) && $reuseToken !== '') {
            Session::put('profit_analysis_import_token', $reuseToken);

            return $reuseToken;
        }

        $oldToken = Session::get('profit_analysis_import_token');
        if (is_string($oldToken) && $oldToken !== '') {
            File::deleteDirectory(storage_path('app/profit-analysis-imports/'.$oldToken));
            Session::forget($this->uploadSessionKey($oldToken));
        }

        $token = Str::uuid()->toString();
        Session::put('profit_analysis_import_token', $token);

        return $token;
    }

    private function uploadSessionKey(string $token): string
    {
        return 'profit_analysis_uploads_'.$token;
    }

    private function clearTempUploads(string $token): void
    {
        if ($token === '') {
            return;
        }

        File::deleteDirectory(storage_path('app/profit-analysis-imports/'.$token));
        Session::forget($this->uploadSessionKey($token));
        Session::forget('profit_analysis_import_token');
    }

    private function uploadedChunkCount(string $chunkDirectory): int
    {
        return count(glob($chunkDirectory.'/*.part') ?: []);
    }

    private function uploadedInputExists(mixed $file): bool
    {
        if (! is_array($file)) {
            return false;
        }

        if (isset($file['files']) && is_array($file['files'])) {
            return collect($file['files'])
                ->contains(fn (array $item): bool => isset($item['path']) && is_file($item['path']));
        }

        return isset($file['path']) && is_file($file['path']);
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
                return (float) str_replace([',', '.'], '', $text);
            }

            return (float) str_replace($decimalSeparator, '.', str_replace($thousandSeparator, '', $text));
        }

        if ($commaCount > 0) {
            return (float) $this->normalizeSingleSeparatorNumber($text, ',');
        }

        if ($dotCount > 0) {
            return (float) $this->normalizeSingleSeparatorNumber($text, '.');
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

        if (strlen($parts[1] ?? '') === 3) {
            return implode('', $parts);
        }

        return $separator === ',' ? str_replace(',', '.', $text) : $text;
    }

    private function totalPeriod(?string $month = null, ?string $marketplace = null, ?int $shopId = null): ?object
    {
        $query = ProfitAnalysisPeriod::query()->with(['shop', 'skuSummaries'])->orderBy('period_month');
        if ($month) {
            $query->whereDate('period_month', $this->monthCarbon($month)->toDateString());
        }
        if ($marketplace) {
            $query->where('marketplace', $marketplace);
        }
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }
        $periods = $query->get();
        if ($periods->isEmpty()) {
            return null;
        }

        $skuSummaries = $this->aggregateSkuSummaries($periods->flatMap->skuSummaries);
        $profit = (float) $periods->sum('profit');
        $orderCount = max(1, (int) $periods->sum('order_count'));

        return (object) [
            'id' => 'all',
            'marketplace' => $marketplace ?? 'total',
            'marketplace_label' => $marketplace ? $this->marketplaceLabel($marketplace) : 'Tổng quan',
            'shop_id' => $shopId,
            'shop' => $shopId ? $periods->first()?->shop : null,
            'label' => $month ? 'T'.$this->monthCarbon($month)->format('n/Y') : 'Tổng tất cả tháng',
            'period_start' => $periods->min('period_start'),
            'period_end' => $periods->max('period_end'),
            'sku_count' => $skuSummaries->count(),
            'missing_cost_count' => 0,
            'order_count' => (int) $periods->sum('order_count'),
            'item_count' => (int) $periods->sum('item_count'),
            'gmv' => (float) $periods->sum('gmv'),
            'settlement_revenue' => (float) $periods->sum('settlement_revenue'),
            'sku_gross_revenue_total' => (float) $periods->sum('sku_gross_revenue_total'),
            'sku_refund_total' => (float) $periods->sum('sku_refund_total'),
            'sku_revenue_total' => (float) $periods->sum('sku_revenue_total'),
            'revenue_adjustment' => (float) $periods->sum('revenue_adjustment'),
            'marketplace_fees' => (float) $periods->sum('marketplace_fees'),
            'ad_cost' => (float) $periods->sum('ad_cost'),
            'cogs' => (float) $periods->sum('cogs'),
            'total_revenue' => (float) $periods->sum('total_revenue'),
            'total_cost' => (float) $periods->sum('total_cost'),
            'profit' => $profit,
            'profit_per_order' => $profit / $orderCount,
            'ad_breakeven' => (float) $periods->sum('ad_breakeven'),
            'completed_order_count' => (int) $periods->sum('completed_order_count'),
            'analytics_order_count' => (int) $periods->sum('analytics_order_count'),
            'source_totals' => [
                'ads' => [
                    'cost_per_order' => 0,
                ],
            ],
            'confirmed_at' => $periods->max('confirmed_at'),
            'confirmedBy' => null,
            'skuSummaries' => $skuSummaries,
            'marketplaceBreakdown' => $this->aggregatePeriods($periods, 'marketplace'),
            'shopBreakdown' => $this->aggregatePeriods($periods, 'shop'),
        ];
    }

    private function aggregatePeriods(Collection $periods, string $groupBy): Collection
    {
        return $periods
            ->groupBy(function (ProfitAnalysisPeriod $period) use ($groupBy): string {
                return $groupBy === 'shop'
                    ? ($period->shop_id ?: 0).':'.$period->marketplace
                    : $period->marketplace;
            })
            ->map(function (Collection $rows) use ($groupBy): object {
                $first = $rows->first();
                $profit = (float) $rows->sum('profit');
                $orderCount = max(1, (int) $rows->sum('order_count'));

                return (object) [
                    'marketplace' => $groupBy === 'shop' ? $first->marketplace : ($first->marketplace ?? 'total'),
                    'marketplace_label' => $this->marketplaceLabel((string) ($first->marketplace ?? 'tiktok')),
                    'shop_id' => $groupBy === 'shop' ? $first->shop_id : null,
                    'shop' => $groupBy === 'shop' ? $first->shop : null,
                    'total_revenue' => (float) $rows->sum('total_revenue'),
                    'completed_order_count' => (int) $rows->sum('completed_order_count'),
                    'order_count' => (int) $rows->sum('order_count'),
                    'item_count' => (int) $rows->sum('item_count'),
                    'cogs' => (float) $rows->sum('cogs'),
                    'marketplace_fees' => (float) $rows->sum('marketplace_fees'),
                    'ad_cost' => (float) $rows->sum('ad_cost'),
                    'profit' => $profit,
                    'profit_per_order' => $profit / $orderCount,
                ];
            })
            ->sortBy(fn (object $row): string => ($row->marketplace ?? '').($row->shop?->name ?? ''))
            ->values();
    }

    private function aggregateSkuSummaries(Collection $summaries): Collection
    {
        return $summaries
            ->groupBy(function ($summary): string {
                $fobSku = trim((string) ($summary->fob_sku ?? ''));

                return $fobSku !== ''
                    ? 'fob:'.$fobSku
                    : 'sku:'.($summary->marketplace ?? $summary->period?->marketplace ?? 'tiktok').':'.$summary->seller_sku;
            })
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $netQuantity = (float) $rows->sum('net_quantity');
                $profit = (float) $rows->sum('profit');
                $sellerSkus = $rows->pluck('seller_sku')->unique()->values()->all();
                $marketplaces = $rows->pluck('marketplace')->unique()->values()->all();

                return (object) [
                    'seller_sku' => implode(', ', array_slice($sellerSkus, 0, 3)).(count($sellerSkus) > 3 ? '...' : ''),
                    'fob_sku' => $first->fob_sku,
                    'product_name' => $first->product_name,
                    'marketplace' => count($marketplaces) > 1 ? 'total' : ($marketplaces[0] ?? 'tiktok'),
                    'unit_cost' => $netQuantity > 0 ? (float) $rows->sum('cogs') / $netQuantity : (float) $first->unit_cost,
                    'quantity_sold' => (float) $rows->sum('quantity_sold'),
                    'quantity_returned' => (float) $rows->sum('quantity_returned'),
                    'net_quantity' => $netQuantity,
                    'original_revenue' => (float) $rows->sum('original_revenue'),
                    'revenue' => (float) $rows->sum('revenue'),
                    'refund_amount' => (float) $rows->sum('refund_amount'),
                    'allocated_revenue_adjustment' => (float) $rows->sum('allocated_revenue_adjustment'),
                    'final_revenue' => (float) $rows->sum('final_revenue'),
                    'cogs' => (float) $rows->sum('cogs'),
                    'allocated_fees' => (float) $rows->sum('allocated_fees'),
                    'allocated_ad_cost' => (float) $rows->sum('allocated_ad_cost'),
                    'profit' => $profit,
                    'profit_per_unit' => $netQuantity > 0 ? $profit / $netQuantity : 0,
                    'status' => $profit >= 0 ? 'profit' : 'loss',
                ];
            })
            ->sortBy('profit')
            ->values();
    }

    private function normalizeMarketplace(string $marketplace): string
    {
        return $marketplace === 'shopee' ? 'shopee' : 'tiktok';
    }

    private function monthCarbon(string $month): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
    }

    private function marketplaceLabel(string $marketplace): string
    {
        return $marketplace === 'shopee' ? 'Shopee' : 'TikTok';
    }

    private function resolveImportShop(string $marketplace, mixed $shopId, ?string $newShopName): ProfitAnalysisShop
    {
        $marketplace = $this->normalizeMarketplace($marketplace);
        $newShopName = trim((string) $newShopName);

        if ($newShopName !== '') {
            return $this->findOrCreateShop($marketplace, $newShopName);
        }

        if ($shopId) {
            $shop = ProfitAnalysisShop::query()
                ->where('marketplace', $marketplace)
                ->find((int) $shopId);

            if ($shop) {
                return $shop;
            }
        }

        return $this->findOrCreateShop($marketplace, 'Shop mặc định');
    }

    private function findOrCreateShop(string $marketplace, string $name): ProfitAnalysisShop
    {
        $marketplace = $this->normalizeMarketplace($marketplace);
        $name = trim($name) !== '' ? trim($name) : 'Shop mặc định';

        return ProfitAnalysisShop::query()->firstOrCreate(
            [
                'marketplace' => $marketplace,
                'normalized_name' => $this->normalizeShopName($name),
            ],
            [
                'name' => $name,
                'is_active' => true,
            ]
        );
    }

    private function normalizeShopName(string $name): string
    {
        $name = Str::ascii(mb_strtolower(trim($name)));
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name) ?? '';

        return trim(preg_replace('/\s+/', ' ', $name) ?? '');
    }
}
