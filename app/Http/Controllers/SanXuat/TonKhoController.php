<?php

namespace App\Http\Controllers\SanXuat;

use App\Http\Controllers\Controller;
use App\Models\DmSize;
use App\Models\DonHang;
use App\Models\DonHangChiTiet;
use App\Models\MatHang;
use App\Models\Mau;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TonKhoController extends Controller
{
    public function index(Request $request): View
    {
        $donHangTable = (new DonHang)->getTable();
        $donHangChiTietTable = (new DonHangChiTiet)->getTable();

        $keyword = trim((string) $request->input('q'));
        $maDon = trim((string) $request->input('ma_don'));
        $maKh = trim((string) $request->input('ma_kh'));
        $matHangId = $request->integer('mat_hang_id') ?: null;
        $mauId = $request->integer('mau_id') ?: null;
        $sizeId = $request->integer('size_id') ?: null;
        $trangThai = trim((string) $request->input('trang_thai'));

        $orderRows = $this->buildOrderRows($donHangTable, $donHangChiTietTable);
        $noOrderRows = $this->buildNoOrderRows($donHangChiTietTable);

        $tonKhos = DB::query()
            ->fromSub($orderRows->unionAll($noOrderRows), 'ton_kho_rows')
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
            ->when($maDon !== '', function (Builder $query) use ($maDon) {
                $query->where('ma_don', 'like', "%{$maDon}%");
            })
            ->when($maKh !== '', function (Builder $query) use ($maKh) {
                $query->where('ma_kh', 'like', "%{$maKh}%");
            })
            ->when($matHangId, function (Builder $query) use ($matHangId) {
                $query->where('mat_hang_id', $matHangId);
            })
            ->when($mauId, function (Builder $query) use ($mauId) {
                $query->where('mau_id', $mauId);
            })
            ->when($sizeId, function (Builder $query) use ($sizeId) {
                $query->where('size_id', $sizeId);
            })
            ->when($trangThai === 'con-hang', function (Builder $query) {
                $query->whereRaw('ton_kho > 0');
            })
            ->when($trangThai === 'het-hang', function (Builder $query) {
                $query->whereRaw('ton_kho = 0');
            })
            ->when($trangThai === 'am-kho', function (Builder $query) {
                $query->whereRaw('ton_kho < 0');
            })
            ->orderByRaw('CASE WHEN ma_don IS NULL THEN 1 ELSE 0 END, COALESCE(ma_don, ""), ma_hang, ten_mau, ten_size')
            ->paginate(10)
            ->withQueryString();

        return view('content.san-xuat.ton-kho.index', [
            'tonKhos' => $tonKhos,
            'keyword' => $keyword,
            'maDon' => $maDon,
            'maKh' => $maKh,
            'matHangId' => $matHangId,
            'mauId' => $mauId,
            'sizeId' => $sizeId,
            'trangThai' => $trangThai,
            'matHangs' => MatHang::query()->orderBy('ten_hang')->get(),
            'maus' => Mau::query()->orderBy('ten_mau')->get(),
            'sizes' => DmSize::query()->orderBy('ten_size')->get(),
        ]);
    }

    private function buildOrderRows(string $donHangTable, string $donHangChiTietTable): Builder
    {
        $orderKeys = DB::table('cat')
            ->selectRaw('cat.don_hang_chi_tiet_id as don_hang_chi_tiet_id')
            ->whereNotNull('cat.don_hang_chi_tiet_id')
            ->whereNull('cat.deleted_at');

        $orderKeys = $orderKeys->unionAll(
            DB::table('qc')
                ->join('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
                ->join('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
                ->selectRaw('COALESCE(qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) as don_hang_chi_tiet_id')
                ->whereRaw('COALESCE(qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is not null')
                ->whereNull('qc.deleted_at')
                ->whereNull('phan_bo_may.deleted_at')
                ->whereNull('cat.deleted_at')
        );

        $orderKeys = $orderKeys->unionAll(
            DB::table('nhap_kho')
                ->join('qc', 'qc.id', '=', 'nhap_kho.qc_id')
                ->join('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
                ->join('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
                ->selectRaw('COALESCE(nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) as don_hang_chi_tiet_id')
                ->whereRaw('COALESCE(nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is not null')
                ->whereNull('nhap_kho.deleted_at')
                ->whereNull('qc.deleted_at')
                ->whereNull('phan_bo_may.deleted_at')
                ->whereNull('cat.deleted_at')
        );

        $orderKeys = $orderKeys->unionAll(
            DB::table('phieu_xuat_kho_chi_tiet')
                ->join('phieu_xuat_kho', 'phieu_xuat_kho.id', '=', 'phieu_xuat_kho_chi_tiet.phieu_xuat_kho_id')
                ->join('nhap_kho', 'nhap_kho.id', '=', 'phieu_xuat_kho_chi_tiet.nhap_kho_id')
                ->join('qc', 'qc.id', '=', 'nhap_kho.qc_id')
                ->join('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
                ->join('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
                ->selectRaw('COALESCE(phieu_xuat_kho_chi_tiet.don_hang_chi_tiet_id, nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) as don_hang_chi_tiet_id')
                ->whereRaw('COALESCE(phieu_xuat_kho_chi_tiet.don_hang_chi_tiet_id, nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is not null')
                ->whereNull('phieu_xuat_kho_chi_tiet.deleted_at')
                ->whereNull('phieu_xuat_kho.deleted_at')
                ->whereNull('nhap_kho.deleted_at')
                ->whereNull('qc.deleted_at')
                ->whereNull('phan_bo_may.deleted_at')
                ->whereNull('cat.deleted_at')
        );

        $orderKeys = DB::query()
            ->fromSub($orderKeys, 'order_keys')
            ->select('don_hang_chi_tiet_id')
            ->distinct();

        $catTotals = DB::table('cat')
            ->selectRaw('cat.don_hang_chi_tiet_id as don_hang_chi_tiet_id, COALESCE(SUM(cat.so_luong_cat), 0) as da_cat')
            ->whereNotNull('cat.don_hang_chi_tiet_id')
            ->whereNull('cat.deleted_at')
            ->groupBy('cat.don_hang_chi_tiet_id');

        $qcTotals = DB::table('qc')
            ->join('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
            ->join('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
            ->selectRaw('
                COALESCE(qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) as don_hang_chi_tiet_id,
                COALESCE(SUM(qc.so_luong_dat), 0) as qc_dat,
                COALESCE(SUM(qc.so_luong_loi), 0) as qc_loi,
                COALESCE(SUM(qc.so_luong_hong), 0) as qc_hong
            ')
            ->whereRaw('COALESCE(qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is not null')
            ->whereNull('qc.deleted_at')
            ->whereNull('phan_bo_may.deleted_at')
            ->whereNull('cat.deleted_at')
            ->groupByRaw('COALESCE(qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id)');

        $nhapTotals = DB::table('nhap_kho')
            ->join('qc', 'qc.id', '=', 'nhap_kho.qc_id')
            ->join('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
            ->join('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
            ->selectRaw("
                COALESCE(nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) as don_hang_chi_tiet_id,
                COALESCE(SUM(CASE WHEN COALESCE(nhap_kho.loai_ton, 'dat') = 'dat' THEN nhap_kho.so_luong_nhap ELSE 0 END), 0) as nhap_dat,
                COALESCE(SUM(CASE WHEN COALESCE(nhap_kho.loai_ton, 'dat') = 'loi' THEN nhap_kho.so_luong_nhap ELSE 0 END), 0) as nhap_loi,
                COALESCE(SUM(CASE WHEN COALESCE(nhap_kho.loai_ton, 'dat') = 'hong' THEN nhap_kho.so_luong_nhap ELSE 0 END), 0) as nhap_hong,
                COALESCE(SUM(nhap_kho.so_luong_nhap), 0) as tong_nhap_kho
            ")
            ->whereRaw('COALESCE(nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is not null')
            ->whereNull('nhap_kho.deleted_at')
            ->whereNull('qc.deleted_at')
            ->whereNull('phan_bo_may.deleted_at')
            ->whereNull('cat.deleted_at')
            ->groupByRaw('COALESCE(nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id)');

        $xuatTotals = DB::table('phieu_xuat_kho_chi_tiet')
            ->join('phieu_xuat_kho', 'phieu_xuat_kho.id', '=', 'phieu_xuat_kho_chi_tiet.phieu_xuat_kho_id')
            ->join('nhap_kho', 'nhap_kho.id', '=', 'phieu_xuat_kho_chi_tiet.nhap_kho_id')
            ->join('qc', 'qc.id', '=', 'nhap_kho.qc_id')
            ->join('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
            ->join('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
            ->selectRaw('COALESCE(phieu_xuat_kho_chi_tiet.don_hang_chi_tiet_id, nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) as don_hang_chi_tiet_id, COALESCE(SUM(phieu_xuat_kho_chi_tiet.so_luong_xuat), 0) as da_xuat')
            ->whereRaw('COALESCE(phieu_xuat_kho_chi_tiet.don_hang_chi_tiet_id, nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is not null')
            ->whereRaw("COALESCE(nhap_kho.loai_ton, 'dat') = 'dat'")
            ->whereNull('phieu_xuat_kho_chi_tiet.deleted_at')
            ->whereNull('phieu_xuat_kho.deleted_at')
            ->whereNull('nhap_kho.deleted_at')
            ->whereNull('qc.deleted_at')
            ->whereNull('phan_bo_may.deleted_at')
            ->whereNull('cat.deleted_at')
            ->groupByRaw('COALESCE(phieu_xuat_kho_chi_tiet.don_hang_chi_tiet_id, nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id)');

        return DB::query()
            ->fromSub($orderKeys, 'order_keys')
            ->join($donHangChiTietTable.' as dct', 'dct.id', '=', 'order_keys.don_hang_chi_tiet_id')
            ->join($donHangTable.' as dh', 'dh.id', '=', 'dct.don_hang_id')
            ->join('dm_mat_hang as mh', 'mh.id', '=', 'dct.mat_hang_id')
            ->join('dm_mau as mau', 'mau.id', '=', 'dct.mau_id')
            ->join('dm_size as sz', 'sz.id', '=', 'dct.size_id')
            ->leftJoinSub($catTotals, 'cat_totals', function ($join) {
                $join->on('order_keys.don_hang_chi_tiet_id', '=', 'cat_totals.don_hang_chi_tiet_id');
            })
            ->leftJoinSub($qcTotals, 'qc_totals', function ($join) {
                $join->on('order_keys.don_hang_chi_tiet_id', '=', 'qc_totals.don_hang_chi_tiet_id');
            })
            ->leftJoinSub($nhapTotals, 'nhap_totals', function ($join) {
                $join->on('order_keys.don_hang_chi_tiet_id', '=', 'nhap_totals.don_hang_chi_tiet_id');
            })
            ->leftJoinSub($xuatTotals, 'xuat_totals', function ($join) {
                $join->on('order_keys.don_hang_chi_tiet_id', '=', 'xuat_totals.don_hang_chi_tiet_id');
            })
            ->selectRaw('
                dct.id as don_hang_chi_tiet_id,
                dh.ma_don,
                dh.ma_kh,
                dct.mat_hang_id,
                dct.mau_id,
                dct.size_id,
                mh.ma_hang,
                mh.ten_hang,
                mau.ten_mau,
                sz.ten_size,
                dct.so_luong_dat,
                COALESCE(cat_totals.da_cat, 0) as da_cat,
                COALESCE(qc_totals.qc_dat, 0) as qc_dat,
                COALESCE(qc_totals.qc_loi, 0) as qc_loi,
                COALESCE(qc_totals.qc_hong, 0) as qc_hong,
                COALESCE(nhap_totals.nhap_dat, 0) as nhap_dat,
                COALESCE(nhap_totals.nhap_loi, 0) as nhap_loi,
                COALESCE(nhap_totals.nhap_hong, 0) as nhap_hong,
                COALESCE(nhap_totals.tong_nhap_kho, 0) as nhap_kho,
                COALESCE(xuat_totals.da_xuat, 0) as da_xuat,
                (COALESCE(nhap_totals.nhap_dat, 0) - COALESCE(xuat_totals.da_xuat, 0)) as ton_dat,
                COALESCE(nhap_totals.nhap_loi, 0) as ton_loi,
                COALESCE(nhap_totals.nhap_hong, 0) as ton_hong,
                (COALESCE(nhap_totals.nhap_dat, 0) - COALESCE(xuat_totals.da_xuat, 0)) as ton_co_the_xuat,
                ((COALESCE(nhap_totals.nhap_dat, 0) - COALESCE(xuat_totals.da_xuat, 0)) + COALESCE(nhap_totals.nhap_loi, 0) + COALESCE(nhap_totals.nhap_hong, 0)) as tong_ton_vat_ly,
                (COALESCE(nhap_totals.nhap_dat, 0) - COALESCE(xuat_totals.da_xuat, 0)) as ton_kho
            ')
            ->whereNull('dct.deleted_at')
            ->whereNull('dh.deleted_at')
            ->whereNull('mh.deleted_at')
            ->whereNull('mau.deleted_at')
            ->whereNull('sz.deleted_at');
    }

    private function buildNoOrderRows(string $donHangChiTietTable): Builder
    {
        $noOrderKeys = DB::table('cat')
            ->selectRaw('cat.mat_hang_id, cat.mau_id, cat.size_id')
            ->whereNull('cat.don_hang_chi_tiet_id')
            ->whereNull('cat.deleted_at');

        $noOrderKeys = $noOrderKeys->unionAll(
            DB::table('qc')
                ->leftJoin('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
                ->selectRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) as mat_hang_id, COALESCE(cat.mau_id, qc.mau_id) as mau_id, COALESCE(cat.size_id, qc.size_id) as size_id')
                ->whereRaw('COALESCE(qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is null')
                ->whereRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) is not null')
                ->whereRaw('COALESCE(cat.mau_id, qc.mau_id) is not null')
                ->whereRaw('COALESCE(cat.size_id, qc.size_id) is not null')
                ->whereNull('qc.deleted_at')
                ->where(function (Builder $query) {
                    $query->whereNull('phan_bo_may.id')->orWhereNull('phan_bo_may.deleted_at');
                })
                ->where(function (Builder $query) {
                    $query->whereNull('cat.id')->orWhereNull('cat.deleted_at');
                })
        );

        $noOrderKeys = $noOrderKeys->unionAll(
            DB::table('nhap_kho')
                ->join('qc', 'qc.id', '=', 'nhap_kho.qc_id')
                ->leftJoin('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
                ->selectRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) as mat_hang_id, COALESCE(cat.mau_id, qc.mau_id) as mau_id, COALESCE(cat.size_id, qc.size_id) as size_id')
                ->whereRaw('COALESCE(nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is null')
                ->whereRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) is not null')
                ->whereRaw('COALESCE(cat.mau_id, qc.mau_id) is not null')
                ->whereRaw('COALESCE(cat.size_id, qc.size_id) is not null')
                ->whereNull('nhap_kho.deleted_at')
                ->whereNull('qc.deleted_at')
                ->where(function (Builder $query) {
                    $query->whereNull('phan_bo_may.id')->orWhereNull('phan_bo_may.deleted_at');
                })
                ->where(function (Builder $query) {
                    $query->whereNull('cat.id')->orWhereNull('cat.deleted_at');
                })
        );

        $noOrderKeys = $noOrderKeys->unionAll(
            DB::table('phieu_xuat_kho_chi_tiet')
                ->join('phieu_xuat_kho', 'phieu_xuat_kho.id', '=', 'phieu_xuat_kho_chi_tiet.phieu_xuat_kho_id')
                ->join('nhap_kho', 'nhap_kho.id', '=', 'phieu_xuat_kho_chi_tiet.nhap_kho_id')
                ->join('qc', 'qc.id', '=', 'nhap_kho.qc_id')
                ->leftJoin('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
                ->selectRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) as mat_hang_id, COALESCE(cat.mau_id, qc.mau_id) as mau_id, COALESCE(cat.size_id, qc.size_id) as size_id')
                ->whereRaw('COALESCE(phieu_xuat_kho_chi_tiet.don_hang_chi_tiet_id, nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is null')
                ->whereRaw("COALESCE(nhap_kho.loai_ton, 'dat') = 'dat'")
                ->whereRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) is not null')
                ->whereRaw('COALESCE(cat.mau_id, qc.mau_id) is not null')
                ->whereRaw('COALESCE(cat.size_id, qc.size_id) is not null')
                ->whereNull('phieu_xuat_kho_chi_tiet.deleted_at')
                ->whereNull('phieu_xuat_kho.deleted_at')
                ->whereNull('nhap_kho.deleted_at')
                ->whereNull('qc.deleted_at')
                ->where(function (Builder $query) {
                    $query->whereNull('phan_bo_may.id')->orWhereNull('phan_bo_may.deleted_at');
                })
                ->where(function (Builder $query) {
                    $query->whereNull('cat.id')->orWhereNull('cat.deleted_at');
                })
        );

        $noOrderKeys = DB::query()
            ->fromSub($noOrderKeys, 'no_order_keys')
            ->select('mat_hang_id', 'mau_id', 'size_id')
            ->distinct();

        $catTotals = DB::table('cat')
            ->selectRaw('cat.mat_hang_id, cat.mau_id, cat.size_id, COALESCE(SUM(cat.so_luong_cat), 0) as da_cat')
            ->whereNull('cat.don_hang_chi_tiet_id')
            ->whereNull('cat.deleted_at')
            ->groupBy('cat.mat_hang_id', 'cat.mau_id', 'cat.size_id');

        $qcTotals = DB::table('qc')
            ->leftJoin('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
            ->leftJoin('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
            ->selectRaw('
                COALESCE(cat.mat_hang_id, qc.mat_hang_id) as mat_hang_id,
                COALESCE(cat.mau_id, qc.mau_id) as mau_id,
                COALESCE(cat.size_id, qc.size_id) as size_id,
                COALESCE(SUM(qc.so_luong_dat), 0) as qc_dat,
                COALESCE(SUM(qc.so_luong_loi), 0) as qc_loi,
                COALESCE(SUM(qc.so_luong_hong), 0) as qc_hong
            ')
            ->whereRaw('COALESCE(qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is null')
            ->whereRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) is not null')
            ->whereRaw('COALESCE(cat.mau_id, qc.mau_id) is not null')
            ->whereRaw('COALESCE(cat.size_id, qc.size_id) is not null')
            ->whereNull('qc.deleted_at')
            ->where(function (Builder $query) {
                $query->whereNull('phan_bo_may.id')->orWhereNull('phan_bo_may.deleted_at');
            })
            ->where(function (Builder $query) {
                $query->whereNull('cat.id')->orWhereNull('cat.deleted_at');
            })
            ->groupByRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id), COALESCE(cat.mau_id, qc.mau_id), COALESCE(cat.size_id, qc.size_id)');

        $nhapTotals = DB::table('nhap_kho')
            ->join('qc', 'qc.id', '=', 'nhap_kho.qc_id')
            ->leftJoin('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
            ->leftJoin('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
            ->selectRaw("
                COALESCE(cat.mat_hang_id, qc.mat_hang_id) as mat_hang_id,
                COALESCE(cat.mau_id, qc.mau_id) as mau_id,
                COALESCE(cat.size_id, qc.size_id) as size_id,
                COALESCE(SUM(CASE WHEN COALESCE(nhap_kho.loai_ton, 'dat') = 'dat' THEN nhap_kho.so_luong_nhap ELSE 0 END), 0) as nhap_dat,
                COALESCE(SUM(CASE WHEN COALESCE(nhap_kho.loai_ton, 'dat') = 'loi' THEN nhap_kho.so_luong_nhap ELSE 0 END), 0) as nhap_loi,
                COALESCE(SUM(CASE WHEN COALESCE(nhap_kho.loai_ton, 'dat') = 'hong' THEN nhap_kho.so_luong_nhap ELSE 0 END), 0) as nhap_hong,
                COALESCE(SUM(nhap_kho.so_luong_nhap), 0) as tong_nhap_kho
            ")
            ->whereRaw('COALESCE(nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is null')
            ->whereRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) is not null')
            ->whereRaw('COALESCE(cat.mau_id, qc.mau_id) is not null')
            ->whereRaw('COALESCE(cat.size_id, qc.size_id) is not null')
            ->whereNull('nhap_kho.deleted_at')
            ->whereNull('qc.deleted_at')
            ->where(function (Builder $query) {
                $query->whereNull('phan_bo_may.id')->orWhereNull('phan_bo_may.deleted_at');
            })
            ->where(function (Builder $query) {
                $query->whereNull('cat.id')->orWhereNull('cat.deleted_at');
            })
            ->groupByRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id), COALESCE(cat.mau_id, qc.mau_id), COALESCE(cat.size_id, qc.size_id)');

        $xuatTotals = DB::table('phieu_xuat_kho_chi_tiet')
            ->join('phieu_xuat_kho', 'phieu_xuat_kho.id', '=', 'phieu_xuat_kho_chi_tiet.phieu_xuat_kho_id')
            ->join('nhap_kho', 'nhap_kho.id', '=', 'phieu_xuat_kho_chi_tiet.nhap_kho_id')
            ->join('qc', 'qc.id', '=', 'nhap_kho.qc_id')
            ->leftJoin('phan_bo_may', 'phan_bo_may.id', '=', 'qc.phan_bo_may_id')
            ->leftJoin('cat', 'cat.id', '=', 'phan_bo_may.cat_id')
            ->selectRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) as mat_hang_id, COALESCE(cat.mau_id, qc.mau_id) as mau_id, COALESCE(cat.size_id, qc.size_id) as size_id, COALESCE(SUM(phieu_xuat_kho_chi_tiet.so_luong_xuat), 0) as da_xuat')
            ->whereRaw('COALESCE(phieu_xuat_kho_chi_tiet.don_hang_chi_tiet_id, nhap_kho.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, cat.don_hang_chi_tiet_id) is null')
            ->whereRaw("COALESCE(nhap_kho.loai_ton, 'dat') = 'dat'")
            ->whereRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id) is not null')
            ->whereRaw('COALESCE(cat.mau_id, qc.mau_id) is not null')
            ->whereRaw('COALESCE(cat.size_id, qc.size_id) is not null')
            ->whereNull('phieu_xuat_kho_chi_tiet.deleted_at')
            ->whereNull('phieu_xuat_kho.deleted_at')
            ->whereNull('nhap_kho.deleted_at')
            ->whereNull('qc.deleted_at')
            ->where(function (Builder $query) {
                $query->whereNull('phan_bo_may.id')->orWhereNull('phan_bo_may.deleted_at');
            })
            ->where(function (Builder $query) {
                $query->whereNull('cat.id')->orWhereNull('cat.deleted_at');
            })
            ->groupByRaw('COALESCE(cat.mat_hang_id, qc.mat_hang_id), COALESCE(cat.mau_id, qc.mau_id), COALESCE(cat.size_id, qc.size_id)');

        return DB::query()
            ->fromSub($noOrderKeys, 'no_order_keys')
            ->join('dm_mat_hang as mh', 'mh.id', '=', 'no_order_keys.mat_hang_id')
            ->join('dm_mau as mau', 'mau.id', '=', 'no_order_keys.mau_id')
            ->join('dm_size as sz', 'sz.id', '=', 'no_order_keys.size_id')
            ->leftJoinSub($catTotals, 'cat_totals', function ($join) {
                $join->on('no_order_keys.mat_hang_id', '=', 'cat_totals.mat_hang_id')
                    ->on('no_order_keys.mau_id', '=', 'cat_totals.mau_id')
                    ->on('no_order_keys.size_id', '=', 'cat_totals.size_id');
            })
            ->leftJoinSub($qcTotals, 'qc_totals', function ($join) {
                $join->on('no_order_keys.mat_hang_id', '=', 'qc_totals.mat_hang_id')
                    ->on('no_order_keys.mau_id', '=', 'qc_totals.mau_id')
                    ->on('no_order_keys.size_id', '=', 'qc_totals.size_id');
            })
            ->leftJoinSub($nhapTotals, 'nhap_totals', function ($join) {
                $join->on('no_order_keys.mat_hang_id', '=', 'nhap_totals.mat_hang_id')
                    ->on('no_order_keys.mau_id', '=', 'nhap_totals.mau_id')
                    ->on('no_order_keys.size_id', '=', 'nhap_totals.size_id');
            })
            ->leftJoinSub($xuatTotals, 'xuat_totals', function ($join) {
                $join->on('no_order_keys.mat_hang_id', '=', 'xuat_totals.mat_hang_id')
                    ->on('no_order_keys.mau_id', '=', 'xuat_totals.mau_id')
                    ->on('no_order_keys.size_id', '=', 'xuat_totals.size_id');
            })
            ->selectRaw('
                null as don_hang_chi_tiet_id,
                null as ma_don,
                null as ma_kh,
                no_order_keys.mat_hang_id,
                no_order_keys.mau_id,
                no_order_keys.size_id,
                mh.ma_hang,
                mh.ten_hang,
                mau.ten_mau,
                sz.ten_size,
                null as so_luong_dat,
                COALESCE(cat_totals.da_cat, 0) as da_cat,
                COALESCE(qc_totals.qc_dat, 0) as qc_dat,
                COALESCE(qc_totals.qc_loi, 0) as qc_loi,
                COALESCE(qc_totals.qc_hong, 0) as qc_hong,
                COALESCE(nhap_totals.nhap_dat, 0) as nhap_dat,
                COALESCE(nhap_totals.nhap_loi, 0) as nhap_loi,
                COALESCE(nhap_totals.nhap_hong, 0) as nhap_hong,
                COALESCE(nhap_totals.tong_nhap_kho, 0) as nhap_kho,
                COALESCE(xuat_totals.da_xuat, 0) as da_xuat,
                (COALESCE(nhap_totals.nhap_dat, 0) - COALESCE(xuat_totals.da_xuat, 0)) as ton_dat,
                COALESCE(nhap_totals.nhap_loi, 0) as ton_loi,
                COALESCE(nhap_totals.nhap_hong, 0) as ton_hong,
                (COALESCE(nhap_totals.nhap_dat, 0) - COALESCE(xuat_totals.da_xuat, 0)) as ton_co_the_xuat,
                ((COALESCE(nhap_totals.nhap_dat, 0) - COALESCE(xuat_totals.da_xuat, 0)) + COALESCE(nhap_totals.nhap_loi, 0) + COALESCE(nhap_totals.nhap_hong, 0)) as tong_ton_vat_ly,
                (COALESCE(nhap_totals.nhap_dat, 0) - COALESCE(xuat_totals.da_xuat, 0)) as ton_kho
            ')
            ->whereNull('mh.deleted_at')
            ->whereNull('mau.deleted_at')
            ->whereNull('sz.deleted_at');
    }
}
