<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Models\ProfitAnalysisPeriod;
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
        $periods = ProfitAnalysisPeriod::query()
            ->with('confirmedBy')
            ->orderByDesc('period_month')
            ->paginate(paginationPerPage())
            ->withQueryString();

        $selectedPeriod = null;
        $isTotalView = $request->input('period') === 'all';
        if ($request->filled('period')) {
            if ($isTotalView) {
                $selectedPeriod = $this->totalPeriod();
            } else {
                $selectedPeriod = ProfitAnalysisPeriod::query()
                    ->with(['skuSummaries' => fn ($query) => $query->orderBy('profit')->orderByDesc('net_quantity')])
                    ->find($request->integer('period'));
            }
        }

        $selectedPeriod ??= ProfitAnalysisPeriod::query()
            ->with(['skuSummaries' => fn ($query) => $query->orderBy('profit')->orderByDesc('net_quantity')])
            ->orderByDesc('period_month')
            ->first();

        return view('content.san-xuat.phan-tich-lai-lo.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'isTotalView' => $isTotalView,
        ]);
    }

    public function create(): View
    {
        $importToken = $this->importToken();

        return view('content.san-xuat.phan-tich-lai-lo.create', [
            'months' => $this->monthOptions(),
            'importToken' => $importToken,
            'uploadedFiles' => Session::get($this->uploadSessionKey($importToken), []),
        ]);
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'import_token' => ['required', 'string', 'max:80'],
            'file_key' => ['required', 'string', 'in:fob_file,analytics_file,ad_file,settlement_file,order_file'],
            'upload_id' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+$/'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:10000'],
            'original_name' => ['required', 'string', 'max:255'],
            'chunk' => ['required', 'file'],
        ], [
            'upload_id.regex' => 'Mã tải lên không hợp lệ.',
        ]);

        if (! Str::endsWith(Str::lower($validated['original_name']), '.xlsx')) {
            return response()->json(['message' => 'Chỉ hỗ trợ file có đuôi .xlsx.'], 422);
        }

        $token = $validated['import_token'];
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
        if (isset($uploads[$fileKey]['path']) && is_file($uploads[$fileKey]['path'])) {
            @unlink($uploads[$fileKey]['path']);
        }

        $uploads[$fileKey] = [
            'path' => $path,
            'name' => $validated['original_name'],
            'size' => filesize($path) ?: 0,
            'uploaded_at' => now()->toDateTimeString(),
        ];
        Session::put($sessionKey, $uploads);

        return response()->json([
            'complete' => true,
            'message' => 'Đã tải lên '.$uploads[$fileKey]['name'],
            'file' => $uploads[$fileKey],
        ]);
    }

    public function preview(Request $request, ProfitAnalysisImportService $service): View|RedirectResponse
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'import_token' => ['required', 'string', 'max:80'],
        ], [
            'period_month.required' => 'Vui lòng chọn tháng phân tích.',
        ]);

        $uploads = Session::get($this->uploadSessionKey($validated['import_token']), []);
        $missing = [];
        foreach (['analytics_file', 'ad_file', 'settlement_file', 'order_file'] as $requiredKey) {
            if (! isset($uploads[$requiredKey]['path']) || ! is_file($uploads[$requiredKey]['path'])) {
                $missing[] = $requiredKey;
            }
        }
        if ($missing !== []) {
            return back()->withInput()->withErrors(['files' => 'Vui lòng tải lên đủ 4 file bắt buộc trước khi kiểm tra dữ liệu.']);
        }

        $files = collect($uploads)
            ->filter(fn (array $file): bool => isset($file['path']) && is_file($file['path']))
            ->map(fn (array $file): string => $file['path'])
            ->all();

        try {
            $preview = $service->preview($files, Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth());
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
            ->route('phan-tich-lai-lo.index', ['period' => $period->id])
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
            ->route('phan-tich-lai-lo.index', ['period' => $profitAnalysisPeriod->id])
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

    private function importToken(): string
    {
        $token = Session::get('profit_analysis_import_token');
        if (! is_string($token) || $token === '') {
            $token = Str::uuid()->toString();
            Session::put('profit_analysis_import_token', $token);
        }

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

    private function totalPeriod(): ?object
    {
        $periods = ProfitAnalysisPeriod::query()->with('skuSummaries')->orderBy('period_month')->get();
        if ($periods->isEmpty()) {
            return null;
        }

        $skuSummaries = $this->aggregateSkuSummaries($periods->flatMap->skuSummaries);
        $profit = (float) $periods->sum('profit');
        $orderCount = max(1, (int) $periods->sum('order_count'));

        return (object) [
            'id' => 'all',
            'label' => 'Tổng tất cả tháng',
            'period_start' => $periods->min('period_start'),
            'period_end' => $periods->max('period_end'),
            'sku_count' => $skuSummaries->count(),
            'missing_cost_count' => 0,
            'order_count' => (int) $periods->sum('order_count'),
            'item_count' => (int) $periods->sum('item_count'),
            'gmv' => (float) $periods->sum('gmv'),
            'settlement_revenue' => (float) $periods->sum('settlement_revenue'),
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
            'confirmed_at' => $periods->max('confirmed_at'),
            'confirmedBy' => null,
            'skuSummaries' => $skuSummaries,
        ];
    }

    private function aggregateSkuSummaries(Collection $summaries): Collection
    {
        return $summaries
            ->groupBy('seller_sku')
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $netQuantity = (float) $rows->sum('net_quantity');
                $profit = (float) $rows->sum('profit');

                return (object) [
                    'seller_sku' => $first->seller_sku,
                    'fob_sku' => $first->fob_sku,
                    'product_name' => $first->product_name,
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
}
