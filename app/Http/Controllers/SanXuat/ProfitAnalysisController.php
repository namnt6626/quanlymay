<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Models\ProfitAnalysisPeriod;
use App\Services\ProfitAnalysis\ProfitAnalysisImportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
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
        return view('content.san-xuat.phan-tich-lai-lo.create', [
            'months' => $this->monthOptions(),
        ]);
    }

    public function preview(Request $request, ProfitAnalysisImportService $service): View|RedirectResponse
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'fob_file' => ['nullable', 'file', 'extensions:xlsx'],
            'analytics_file' => ['required', 'file', 'extensions:xlsx'],
            'ad_file' => ['required', 'file', 'extensions:xlsx'],
            'settlement_file' => ['required', 'file', 'extensions:xlsx'],
            'order_file' => ['required', 'file', 'extensions:xlsx'],
        ], [
            'period_month.required' => 'Vui lòng chọn tháng phân tích.',
            '*.required' => 'Vui lòng upload đủ các file bắt buộc.',
            '*.extensions' => 'Chỉ hỗ trợ file có đuôi .xlsx.',
        ]);

        $files = [];
        foreach (['fob_file', 'analytics_file', 'ad_file', 'settlement_file', 'order_file'] as $key) {
            if ($request->hasFile($key)) {
                $files[$key] = $request->file($key)->getRealPath();
            }
        }

        try {
            $preview = $service->preview($files, Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth());
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['files' => $exception->getMessage()]);
        }

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

        return redirect()
            ->route('phan-tich-lai-lo.index', ['period' => $period->id])
            ->with('success', 'Đã cập nhật dữ liệu '.$period->label.' thành công.');
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
            'marketplace_fees' => (float) $periods->sum('marketplace_fees'),
            'ad_cost' => (float) $periods->sum('ad_cost'),
            'cogs' => (float) $periods->sum('cogs'),
            'total_revenue' => (float) $periods->sum('total_revenue'),
            'total_cost' => (float) $periods->sum('total_cost'),
            'profit' => $profit,
            'profit_per_order' => $profit / $orderCount,
            'ad_breakeven' => (float) $periods->sum('ad_breakeven'),
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
                    'revenue' => (float) $rows->sum('revenue'),
                    'refund_amount' => (float) $rows->sum('refund_amount'),
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
