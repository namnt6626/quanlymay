<?php

namespace App\Http\Controllers\BaoCao;

use App\Http\Controllers\Controller;
use App\Models\DonHangChiTiet;
use App\Services\BaoCao\BaoCaoTongHopDonHangService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BaoCaoTongHopDonHangController extends Controller
{
    public function __invoke(Request $request, BaoCaoTongHopDonHangService $service): View
    {
        $keyword = trim((string) $request->input('q'));
        $maDon = trim((string) $request->input('ma_don'));
        $maKh = trim((string) $request->input('ma_kh'));
        $matHangId = $request->integer('mat_hang_id') ?: null;
        $mauId = $request->integer('mau_id') ?: null;
        $sizeId = $request->integer('size_id') ?: null;
        $ngayNhanTu = trim((string) $request->input('ngay_nhan_tu'));
        $ngayNhanDen = trim((string) $request->input('ngay_nhan_den'));
        $hanGiaoTu = trim((string) $request->input('han_giao_tu'));
        $hanGiaoDen = trim((string) $request->input('han_giao_den'));

        $rows = $service->query()
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('ma_don', 'like', "%{$keyword}%")
                        ->orWhere('ma_kh', 'like', "%{$keyword}%")
                        ->orWhere('ma_hang', 'like', "%{$keyword}%")
                        ->orWhere('ten_hang', 'like', "%{$keyword}%")
                        ->orWhere('ten_mau', 'like', "%{$keyword}%")
                        ->orWhere('ten_size', 'like', "%{$keyword}%");
                });
            })
            ->when($maDon !== '', fn (Builder $query) => $query->where('ma_don', 'like', "%{$maDon}%"))
            ->when($maKh !== '', fn (Builder $query) => $query->where('ma_kh', 'like', "%{$maKh}%"))
            ->when($matHangId, fn (Builder $query) => $query->where('dct.mat_hang_id', $matHangId))
            ->when($mauId, fn (Builder $query) => $query->where('dct.mau_id', $mauId))
            ->when($sizeId, fn (Builder $query) => $query->where('dct.size_id', $sizeId))
            ->when($ngayNhanTu !== '', fn (Builder $query) => $query->whereDate('ngay_nhan', '>=', $ngayNhanTu))
            ->when($ngayNhanDen !== '', fn (Builder $query) => $query->whereDate('ngay_nhan', '<=', $ngayNhanDen))
            ->when($hanGiaoTu !== '', fn (Builder $query) => $query->whereDate('han_giao', '>=', $hanGiaoTu))
            ->when($hanGiaoDen !== '', fn (Builder $query) => $query->whereDate('han_giao', '<=', $hanGiaoDen))
            ->orderByDesc('ngay_nhan')
            ->orderBy('ma_don')
            ->orderBy('don_hang_chi_tiet_id')
            ->paginate(paginationPerPage())
            ->withQueryString();

        $donHangChiTietTable = (new DonHangChiTiet)->getTable();

        return view('content.bao-cao.tong-hop-don-hang.index', [
            'rows' => $rows,
            'keyword' => $keyword,
            'maDon' => $maDon,
            'maKh' => $maKh,
            'matHangId' => $matHangId,
            'mauId' => $mauId,
            'sizeId' => $sizeId,
            'ngayNhanTu' => $ngayNhanTu,
            'ngayNhanDen' => $ngayNhanDen,
            'hanGiaoTu' => $hanGiaoTu,
            'hanGiaoDen' => $hanGiaoDen,
            'matHangs' => DB::table($donHangChiTietTable.' as dct')
                ->join('dm_mat_hang as mh', 'mh.id', '=', 'dct.mat_hang_id')
                ->select('mh.id', 'mh.ma_hang', 'mh.ten_hang')
                ->whereNull('dct.deleted_at')
                ->whereNull('mh.deleted_at')
                ->distinct()
                ->orderBy('mh.ten_hang')
                ->get(),
            'maus' => DB::table($donHangChiTietTable.' as dct')
                ->join('dm_mau as mau', 'mau.id', '=', 'dct.mau_id')
                ->select('mau.id', 'mau.ten_mau')
                ->whereNull('dct.deleted_at')
                ->whereNull('mau.deleted_at')
                ->distinct()
                ->orderBy('mau.ten_mau')
                ->get(),
            'sizes' => DB::table($donHangChiTietTable.' as dct')
                ->join('dm_size as sz', 'sz.id', '=', 'dct.size_id')
                ->select('sz.id', 'sz.ten_size')
                ->whereNull('dct.deleted_at')
                ->whereNull('sz.deleted_at')
                ->distinct()
                ->orderBy('sz.ten_size')
                ->get(),
        ]);
    }
}
