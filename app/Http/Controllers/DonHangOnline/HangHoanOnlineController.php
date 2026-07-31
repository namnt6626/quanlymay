<?php

namespace App\Http\Controllers\DonHangOnline;

use App\Http\Controllers\Controller;
use App\Http\Requests\HangHoanOnline\StoreHangHoanOnlineRequest;
use App\Http\Requests\HangHoanOnline\UpdateHangHoanOnlineRequest;
use App\Models\HangHoanOnline;
use App\Models\HangHoanOnlineChiTiet;
use App\Services\HangHoanOnline\HangHoanOnlineImportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use RuntimeException;

class HangHoanOnlineController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q')),
            'seller_sku' => trim((string) $request->input('seller_sku')),
            'return_status' => trim((string) $request->input('return_status')),
            'return_reason' => trim((string) $request->input('return_reason')),
            'tu_ngay' => trim((string) $request->input('tu_ngay')),
            'den_ngay' => trim((string) $request->input('den_ngay')),
            'per_page' => paginationPerPage(),
        ];

        $detailFilter = function ($detail) use ($filters): void {
            $detail
                ->when($filters['q'] !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($filters): void {
                    $keyword = '%'.$filters['q'].'%';
                    $query->where('order_id', 'like', $keyword)
                        ->orWhere('return_order_id', 'like', $keyword)
                        ->orWhere('ten_san_pham', 'like', $keyword)
                        ->orWhere('sku_name', 'like', $keyword)
                        ->orWhere('tracking_id', 'like', $keyword);
                }))
                ->when($filters['seller_sku'] !== '', fn (Builder $query) => $query->where('seller_sku', $filters['seller_sku']))
                ->when($filters['return_status'] !== '', fn (Builder $query) => $query->where('return_status', $filters['return_status']))
                ->when($filters['return_reason'] !== '', fn (Builder $query) => $query->where('return_reason', $filters['return_reason']));
        };

        $returns = HangHoanOnline::query()
            ->with(['chiTiets' => $detailFilter, 'createdBy'])
            ->withSum(['chiTiets as tong_so_luong_loc' => $detailFilter], 'so_luong_hoan')
            ->withSum(['chiTiets as tong_so_luong_cong_ton_loc' => fn ($query) => $detailFilter($query->where('cong_ton', true))], 'so_luong_hoan')
            ->when($filters['q'] !== '' || $filters['seller_sku'] !== '' || $filters['return_status'] !== '' || $filters['return_reason'] !== '', fn (Builder $query) => $query->whereHas('chiTiets', $detailFilter))
            ->when($filters['tu_ngay'] !== '', fn (Builder $query) => $query->whereDate('ngay_hoan', '>=', $filters['tu_ngay']))
            ->when($filters['den_ngay'] !== '', fn (Builder $query) => $query->whereDate('ngay_hoan', '<=', $filters['den_ngay']))
            ->orderByDesc('ngay_hoan')
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('content.don-hang-online.hang-hoan.index', [
            'returns' => $returns,
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    public function create(): View
    {
        return view('content.don-hang-online.hang-hoan.create');
    }

    public function store(StoreHangHoanOnlineRequest $request, HangHoanOnlineImportService $service): RedirectResponse
    {
        $this->save($request->validated(), $service, null, (int) $request->user()->id);

        return redirect()->route('hang-hoan-online.index')->with('success', 'Đã thêm hàng hoàn online.');
    }

    public function edit(HangHoanOnline $hangHoanOnline): View
    {
        $hangHoanOnline->load('chiTiets');

        return view('content.don-hang-online.hang-hoan.edit', ['returnBatch' => $hangHoanOnline]);
    }

    public function update(UpdateHangHoanOnlineRequest $request, HangHoanOnlineImportService $service, HangHoanOnline $hangHoanOnline): RedirectResponse
    {
        $this->save($request->validated(), $service, $hangHoanOnline, (int) $request->user()->id);

        return redirect()->route('hang-hoan-online.index')->with('success', 'Đã cập nhật hàng hoàn online.');
    }

    public function destroy(HangHoanOnline $hangHoanOnline): RedirectResponse
    {
        $hangHoanOnline->delete();

        return back()->with('success', 'Đã xóa phiếu hàng hoàn.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:hang_hoan_online,id'],
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một phiếu hàng hoàn để xóa.',
            'ids.min' => 'Vui lòng chọn ít nhất một phiếu hàng hoàn để xóa.',
        ])['ids'];

        $returns = HangHoanOnline::query()->whereIn('id', $ids)->get();

        DB::transaction(fn () => $returns->each->delete());

        return redirect()
            ->route('hang-hoan-online.index', $request->query())
            ->with('success', 'Đã xóa '.$returns->count().' phiếu hàng hoàn.');
    }

    public function importForm(): View
    {
        return view('content.don-hang-online.hang-hoan.import');
    }

    public function preview(Request $request, HangHoanOnlineImportService $service): View|RedirectResponse
    {
        $validated = $request->validate([
            'file_excel' => ['required', 'file', 'mimes:xlsx'],
        ], [
            'file_excel.required' => 'Vui lòng chọn file hàng hoàn.',
        ]);

        try {
            $preview = $service->preview($validated['file_excel']->getRealPath());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['file_excel' => $exception->getMessage()]);
        }

        $preview['file_name'] = $validated['file_excel']->getClientOriginalName();
        $previewKey = 'hang_hoan_online_preview_'.str()->uuid()->toString();
        Session::put($previewKey, $preview);

        return view('content.don-hang-online.hang-hoan.preview', [
            'previewKey' => $previewKey,
            'preview' => $preview,
        ]);
    }

    public function commit(Request $request, HangHoanOnlineImportService $service): RedirectResponse
    {
        $validated = $request->validate(['preview_key' => ['required', 'string']]);
        $preview = Session::get($validated['preview_key']);
        if (! is_array($preview)) {
            return redirect()->route('hang-hoan-online.import')->withErrors(['file_excel' => 'Phiên preview đã hết hạn, vui lòng import lại.']);
        }

        $stats = DB::transaction(function () use ($preview, $service, $request): array {
            $rows = $preview['rows'];
            $batch = HangHoanOnline::query()->create([
                'ngay_hoan' => $preview['summary']['to_date'] ?? now()->toDateString(),
                'tu_ngay' => $preview['summary']['from_date'] ?? null,
                'den_ngay' => $preview['summary']['to_date'] ?? null,
                'source' => 'import',
                'ten_file' => $preview['file_name'] ?? null,
                'tong_dong' => (int) $preview['summary']['row_count'],
                'tong_so_luong' => (float) $preview['summary']['return_quantity'],
                'tong_so_luong_cong_ton' => (float) $preview['summary']['stock_quantity'],
                'created_by' => (int) $request->user()->id,
            ]);

            $stats = ['created' => 0, 'updated' => 0];
            $affectedBatchIds = [$batch->id];

            foreach ($rows as $row) {
                $row['hang_hoan_online_id'] = $batch->id;
                $row['cong_ton'] = $service->shouldCountStock($row);
                $row['dedupe_key'] = $service->dedupeKey($row);
                $result = $this->upsertDetail($row, false);
                $stats[$result['created'] ? 'created' : 'updated']++;
                $affectedBatchIds[] = $result['previous_batch_id'];
                $affectedBatchIds[] = $result['current_batch_id'];
            }

            $this->syncBatchSummaries($affectedBatchIds);

            return $stats;
        });

        Session::forget($validated['preview_key']);

        return redirect()
            ->route('hang-hoan-online.index')
            ->with('success', 'Đã import file hàng hoàn: thêm mới '.$stats['created'].' dòng, cập nhật '.$stats['updated'].' dòng trùng.');
    }

    private function save(array $data, HangHoanOnlineImportService $service, ?HangHoanOnline $current, int $userId): HangHoanOnline
    {
        return DB::transaction(function () use ($data, $service, $current, $userId): HangHoanOnline {
            $rows = collect($data['chi_tiets'])->map(function (array $row) use ($service): array {
                $row['return_type'] = trim((string) ($row['return_type'] ?? 'Return and refund')) ?: 'Return and refund';
                $row['return_status'] = trim((string) $row['return_status']);
                $row['cong_ton'] = $service->shouldCountStock($row);
                $row['dedupe_key'] = $service->dedupeKey($row);

                return $row;
            })->values();

            $batch = $current ?: HangHoanOnline::query()->create([
                'ngay_hoan' => $data['ngay_hoan'],
                'source' => 'manual',
                'created_by' => $userId,
            ]);

            $batch->update([
                'ngay_hoan' => $data['ngay_hoan'],
                'tu_ngay' => $data['ngay_hoan'],
                'den_ngay' => $data['ngay_hoan'],
                'source' => $current?->source ?: 'manual',
                'tong_dong' => $rows->count(),
                'tong_so_luong' => (float) $rows->sum('so_luong_hoan'),
                'tong_so_luong_cong_ton' => (float) $rows->where('cong_ton', true)->sum('so_luong_hoan'),
                'ghi_chu' => $data['ghi_chu'] ?? null,
                'created_by' => $batch->created_by ?: $userId,
            ]);

            if ($current) {
                $batch->chiTiets()->delete();
            }

            foreach ($rows as $row) {
                $row['hang_hoan_online_id'] = $batch->id;
                $this->upsertDetail($row, true);
            }

            return $batch;
        });
    }

    /**
     * @return array{created: bool, previous_batch_id: ?int, current_batch_id: int}
     */
    private function upsertDetail(array $row, bool $moveExistingToBatch): array
    {
        $detail = HangHoanOnlineChiTiet::withTrashed()->where('dedupe_key', $row['dedupe_key'])->first();
        if ($detail) {
            $previousBatchId = $detail->hang_hoan_online_id;
            if (! $detail->trashed() && ! $moveExistingToBatch) {
                unset($row['hang_hoan_online_id']);
            }

            $detail->restore();
            $detail->update($row);

            return [
                'created' => false,
                'previous_batch_id' => $previousBatchId,
                'current_batch_id' => (int) $detail->hang_hoan_online_id,
            ];
        }

        $detail = HangHoanOnlineChiTiet::query()->create($row);

        return [
            'created' => true,
            'previous_batch_id' => null,
            'current_batch_id' => (int) $detail->hang_hoan_online_id,
        ];
    }

    /**
     * @param array<int, int|null> $batchIds
     */
    private function syncBatchSummaries(array $batchIds): void
    {
        $ids = collect($batchIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        HangHoanOnline::query()
            ->whereIn('id', $ids)
            ->with('chiTiets')
            ->get()
            ->each(function (HangHoanOnline $batch): void {
                if ($batch->chiTiets->isEmpty()) {
                    $batch->delete();

                    return;
                }

                $dates = $batch->chiTiets
                    ->map(fn (HangHoanOnlineChiTiet $detail): ?Carbon => $detail->refund_time ?: $detail->time_requested)
                    ->filter()
                    ->map(fn (Carbon $date): string => $date->toDateString())
                    ->values();

                $batch->update([
                    'ngay_hoan' => $dates->isNotEmpty() ? $dates->max() : $batch->ngay_hoan,
                    'tu_ngay' => $dates->isNotEmpty() ? $dates->min() : $batch->tu_ngay,
                    'den_ngay' => $dates->isNotEmpty() ? $dates->max() : $batch->den_ngay,
                    'tong_dong' => $batch->chiTiets->count(),
                    'tong_so_luong' => (float) $batch->chiTiets->sum('so_luong_hoan'),
                    'tong_so_luong_cong_ton' => (float) $batch->chiTiets->where('cong_ton', true)->sum('so_luong_hoan'),
                ]);
            });
    }

    private function filterOptions(): array
    {
        return [
            'sellerSkus' => HangHoanOnlineChiTiet::query()->whereNotNull('seller_sku')->distinct()->orderBy('seller_sku')->pluck('seller_sku'),
            'statuses' => HangHoanOnlineChiTiet::query()->whereNotNull('return_status')->distinct()->orderBy('return_status')->pluck('return_status'),
            'reasons' => HangHoanOnlineChiTiet::query()->whereNotNull('return_reason')->where('return_reason', '<>', '')->distinct()->orderBy('return_reason')->pluck('return_reason'),
        ];
    }
}
