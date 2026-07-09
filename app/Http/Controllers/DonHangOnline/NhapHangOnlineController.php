<?php

namespace App\Http\Controllers\DonHangOnline;

use App\Http\Controllers\Controller;
use App\Http\Requests\NhapHangOnline\StoreNhapHangOnlineRequest;
use App\Http\Requests\NhapHangOnline\UpdateNhapHangOnlineRequest;
use App\Models\NhapHangOnline;
use App\Models\NhapHangOnlineChiTiet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NhapHangOnlineController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'ten_san_pham' => trim((string) $request->input('ten_san_pham')),
            'mau' => trim((string) $request->input('mau')),
            'size' => trim((string) $request->input('size')),
            'tu_ngay' => trim((string) $request->input('tu_ngay')),
            'den_ngay' => trim((string) $request->input('den_ngay')),
            'per_page' => paginationPerPage(),
        ];

        $detailFilter = function ($detail) use ($filters): void {
            $detail
                ->when($filters['ten_san_pham'] !== '', fn (Builder $query) => $query->where('ten_san_pham', 'like', '%'.$filters['ten_san_pham'].'%'))
                ->when($filters['mau'] !== '', fn (Builder $query) => $query->where('mau', $filters['mau']))
                ->when($filters['size'] !== '', fn (Builder $query) => $query->where('size', $filters['size']));
        };

        $imports = NhapHangOnline::query()
            ->with(['chiTiets' => $detailFilter])
            ->withSum(['chiTiets as tong_so_luong' => $detailFilter], 'so_luong')
            ->withSum(['chiTiets as tong_thanh_tien' => $detailFilter], 'thanh_tien')
            ->when($filters['ten_san_pham'] !== '' || $filters['mau'] !== '' || $filters['size'] !== '', fn (Builder $query) => $query->whereHas('chiTiets', $detailFilter))
            ->when($filters['tu_ngay'] !== '', fn (Builder $query) => $query->whereDate('ngay_nhap', '>=', $filters['tu_ngay']))
            ->when($filters['den_ngay'] !== '', fn (Builder $query) => $query->whereDate('ngay_nhap', '<=', $filters['den_ngay']))
            ->orderByDesc('ngay_nhap')
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $dates = $imports->getCollection()
            ->pluck('ngay_nhap')
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->values();

        $dailyTotals = NhapHangOnline::query()
            ->join('nhap_hang_online_chi_tiet', 'nhap_hang_online_chi_tiet.nhap_hang_online_id', '=', 'nhap_hang_online.id')
            ->whereNull('nhap_hang_online_chi_tiet.deleted_at')
            ->whereIn('ngay_nhap', $dates)
            ->selectRaw('nhap_hang_online.ngay_nhap, SUM(nhap_hang_online_chi_tiet.thanh_tien) as tong_tien')
            ->groupBy('ngay_nhap')
            ->pluck('tong_tien', 'ngay_nhap');

        $filterOptions = $this->filterOptions();

        return view('content.don-hang-online.nhap-hang.index', compact('imports', 'filters', 'dailyTotals', 'filterOptions'));
    }

    public function create(): View
    {
        return view('content.don-hang-online.nhap-hang.create');
    }

    public function store(StoreNhapHangOnlineRequest $request): RedirectResponse
    {
        $this->save($request->validated());

        return redirect()->route('nhap-hang-online.index')->with('success', 'Thêm nhập hàng online thành công.');
    }

    public function edit(NhapHangOnline $nhapHangOnline): View
    {
        $nhapHangOnline->load('chiTiets');

        return view('content.don-hang-online.nhap-hang.edit', ['import' => $nhapHangOnline]);
    }

    public function update(UpdateNhapHangOnlineRequest $request, NhapHangOnline $nhapHangOnline): RedirectResponse
    {
        $this->save($request->validated(), $nhapHangOnline);

        return redirect()->route('nhap-hang-online.index')->with('success', 'Cập nhật nhập hàng online thành công.');
    }

    public function destroy(NhapHangOnline $nhapHangOnline): RedirectResponse
    {
        $nhapHangOnline->delete();

        return back()->with('success', 'Xóa nhập hàng online thành công.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:nhap_hang_online,id'],
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một phiếu nhập hàng để xóa.',
            'ids.min' => 'Vui lòng chọn ít nhất một phiếu nhập hàng để xóa.',
        ])['ids'];

        $imports = NhapHangOnline::query()->whereIn('id', $ids)->get();

        DB::transaction(fn () => $imports->each->delete());

        return redirect()
            ->route('nhap-hang-online.index', $request->query())
            ->with('success', 'Đã xóa '.$imports->count().' phiếu nhập hàng.');
    }

    private function save(array $data, ?NhapHangOnline $current = null): NhapHangOnline
    {
        return DB::transaction(function () use ($data, $current): NhapHangOnline {
            $import = $current ?: NhapHangOnline::query()->create([
                'ngay_nhap' => $data['ngay_nhap'],
                'ghi_chu' => null,
            ]);

            if ($current) {
                $import->update([
                    'ngay_nhap' => $data['ngay_nhap'],
                    'ghi_chu' => null,
                ]);
                $import->chiTiets()->delete();
            }

            foreach ($data['chi_tiets'] as $detail) {
                $quantity = (float) $detail['so_luong'];
                $unitPrice = (float) $detail['don_gia'];
                $import->chiTiets()->create([
                    'ten_san_pham' => trim((string) $detail['ten_san_pham']),
                    'mau' => trim((string) ($detail['mau'] ?? '')) ?: null,
                    'size' => trim((string) ($detail['size'] ?? '')) ?: null,
                    'so_luong' => $quantity,
                    'don_gia' => $unitPrice,
                    'thanh_tien' => $quantity * $unitPrice,
                ]);
            }

            return $import;
        });
    }

    private function filterOptions(): array
    {
        return [
            'products' => NhapHangOnlineChiTiet::query()
                ->select('ten_san_pham')
                ->whereNotNull('ten_san_pham')
                ->distinct()
                ->orderBy('ten_san_pham')
                ->pluck('ten_san_pham'),
            'colors' => NhapHangOnlineChiTiet::query()
                ->select('mau')
                ->whereNotNull('mau')
                ->where('mau', '<>', '')
                ->distinct()
                ->orderBy('mau')
                ->pluck('mau'),
            'sizes' => NhapHangOnlineChiTiet::query()
                ->select('size')
                ->whereNotNull('size')
                ->where('size', '<>', '')
                ->distinct()
                ->orderBy('size')
                ->pluck('size'),
        ];
    }
}
