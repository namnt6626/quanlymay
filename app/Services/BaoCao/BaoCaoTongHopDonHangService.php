<?php

namespace App\Services\BaoCao;

use App\Models\DonHang;
use App\Models\DonHangChiTiet;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BaoCaoTongHopDonHangService
{
    public function query(): Builder
    {
        $donHangTable = (new DonHang)->getTable();
        $donHangChiTietTable = (new DonHangChiTiet)->getTable();

        $catTotals = DB::table('cat')
            ->selectRaw('cat.don_hang_chi_tiet_id as don_hang_chi_tiet_id, COALESCE(SUM(cat.so_luong_cat), 0) as da_cat')
            ->whereNotNull('cat.don_hang_chi_tiet_id')
            ->whereNull('cat.deleted_at')
            ->groupBy('cat.don_hang_chi_tiet_id');

        $phanBoTotals = DB::table('phan_bo_may as pbm')
            ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->selectRaw('COALESCE(pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id, COALESCE(SUM(pbm.so_luong_giao), 0) as da_giao_may')
            ->whereRaw('COALESCE(pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) is not null')
            ->whereNull('pbm.deleted_at')
            ->whereNull('c.deleted_at')
            ->groupByRaw('COALESCE(pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)');

        $qcTotals = DB::table('qc')
            ->join('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
            ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->selectRaw('
                COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id,
                COALESCE(SUM(qc.so_luong_dat), 0) as qc_dat,
                COALESCE(SUM(qc.so_luong_loi), 0) as qc_loi,
                COALESCE(SUM(qc.so_luong_hong), 0) as qc_hong
            ')
            ->whereRaw('COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) is not null')
            ->whereNull('qc.deleted_at')
            ->whereNull('pbm.deleted_at')
            ->whereNull('c.deleted_at')
            ->groupByRaw('COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)');

        $nhapTotals = DB::table('nhap_kho as nk')
            ->join('qc', 'qc.id', '=', 'nk.qc_id')
            ->join('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
            ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->selectRaw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id, COALESCE(SUM(nk.so_luong_nhap), 0) as nhap_kho')
            ->whereRaw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) is not null')
            ->whereNull('nk.deleted_at')
            ->whereNull('qc.deleted_at')
            ->whereNull('pbm.deleted_at')
            ->whereNull('c.deleted_at')
            ->groupByRaw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)');

        $xuatTotals = DB::table('phieu_xuat_kho_chi_tiet as pxct')
            ->join('phieu_xuat_kho as px', 'px.id', '=', 'pxct.phieu_xuat_kho_id')
            ->join('nhap_kho as nk', 'nk.id', '=', 'pxct.nhap_kho_id')
            ->join('qc', 'qc.id', '=', 'nk.qc_id')
            ->join('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
            ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->selectRaw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id, COALESCE(SUM(pxct.so_luong_xuat), 0) as da_xuat')
            ->whereRaw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) is not null')
            ->whereNull('pxct.deleted_at')
            ->whereNull('px.deleted_at')
            ->whereNull('nk.deleted_at')
            ->whereNull('qc.deleted_at')
            ->whereNull('pbm.deleted_at')
            ->whereNull('c.deleted_at')
            ->groupByRaw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)');

        return DB::table($donHangChiTietTable.' as dct')
            ->join($donHangTable.' as dh', 'dh.id', '=', 'dct.don_hang_id')
            ->join('dm_mat_hang as mh', 'mh.id', '=', 'dct.mat_hang_id')
            ->join('dm_mau as mau', 'mau.id', '=', 'dct.mau_id')
            ->join('dm_size as sz', 'sz.id', '=', 'dct.size_id')
            ->leftJoinSub($catTotals, 'cat_totals', fn ($join) => $join->on('dct.id', '=', 'cat_totals.don_hang_chi_tiet_id'))
            ->leftJoinSub($phanBoTotals, 'phan_bo_totals', fn ($join) => $join->on('dct.id', '=', 'phan_bo_totals.don_hang_chi_tiet_id'))
            ->leftJoinSub($qcTotals, 'qc_totals', fn ($join) => $join->on('dct.id', '=', 'qc_totals.don_hang_chi_tiet_id'))
            ->leftJoinSub($nhapTotals, 'nhap_totals', fn ($join) => $join->on('dct.id', '=', 'nhap_totals.don_hang_chi_tiet_id'))
            ->leftJoinSub($xuatTotals, 'xuat_totals', fn ($join) => $join->on('dct.id', '=', 'xuat_totals.don_hang_chi_tiet_id'))
            ->whereNull('dct.deleted_at')
            ->whereNull('dh.deleted_at')
            ->whereNull('mh.deleted_at')
            ->whereNull('mau.deleted_at')
            ->whereNull('sz.deleted_at')
            ->selectRaw("
                dct.id as don_hang_chi_tiet_id,
                dh.ma_don,
                dh.ma_kh,
                dh.ngay_nhan,
                dh.han_giao,
                dct.mat_hang_id,
                dct.mau_id,
                dct.size_id,
                mh.ma_hang,
                mh.ten_hang,
                mau.ten_mau,
                sz.ten_size,
                dct.so_luong_dat,
                COALESCE(cat_totals.da_cat, 0) as da_cat,
                COALESCE(phan_bo_totals.da_giao_may, 0) as da_giao_may,
                COALESCE(qc_totals.qc_dat, 0) as qc_dat,
                COALESCE(qc_totals.qc_loi, 0) + COALESCE(qc_totals.qc_hong, 0) as qc_loi,
                COALESCE(nhap_totals.nhap_kho, 0) as nhap_kho,
                COALESCE(xuat_totals.da_xuat, 0) as da_xuat,
                (COALESCE(nhap_totals.nhap_kho, 0) - COALESCE(xuat_totals.da_xuat, 0)) as ton_kho,
                (dct.so_luong_dat - COALESCE(cat_totals.da_cat, 0)) as con_phai_cat,
                (dct.so_luong_dat - COALESCE(phan_bo_totals.da_giao_may, 0)) as con_phai_giao,
                CASE
                    WHEN COALESCE(cat_totals.da_cat, 0) = 0
                        AND COALESCE(phan_bo_totals.da_giao_may, 0) = 0
                        AND COALESCE(qc_totals.qc_dat, 0) = 0
                        AND COALESCE(nhap_totals.nhap_kho, 0) = 0
                        AND COALESCE(xuat_totals.da_xuat, 0) = 0
                        THEN 'Chưa sản xuất'
                    WHEN (COALESCE(nhap_totals.nhap_kho, 0) - COALESCE(xuat_totals.da_xuat, 0)) = dct.so_luong_dat AND dct.so_luong_dat > 0
                        THEN 'Hoàn thành'
                    ELSE 'Đang xử lý'
                END as tinh_trang
            ");
    }
}
