<?php

namespace App\Services\Dashboard;

use App\Models\DonHang;
use App\Models\DonHangChiTiet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getQuickSummary(array $filters = []): array
    {
        $orderRows = DB::query()
            ->fromSub($this->orderLineProgressQuery($filters), 'progress')
            ->selectRaw('
                COALESCE(SUM(so_luong_dat), 0) as tong_sl_dat,
                COALESCE(SUM(con_cat), 0) as con_cat,
                COALESCE(SUM(con_giao), 0) as con_giao,
                COALESCE(SUM(CASE WHEN da_cat < so_luong_dat THEN 1 ELSE 0 END), 0) as dong_thieu_cat,
                COALESCE(SUM(CASE WHEN nhap_dat < so_luong_dat THEN 1 ELSE 0 END), 0) as dong_thieu_hang_kho
            ')
            ->first();
        $productionRows = $this->getCumulativeProductionSummary($filters);

        return [
            'tong_sl_dat' => (float) ($orderRows->tong_sl_dat ?? 0),
            'da_cat' => $productionRows['da_cat'],
            'da_giao_may' => $productionRows['da_giao_may'],
            'qc_dat' => $productionRows['qc_dat'],
            'qc_loi' => $productionRows['qc_loi'],
            'nhap_kho' => $productionRows['nhap_kho'],
            'da_xuat' => $productionRows['da_xuat'],
            'ton_kho' => $productionRows['ton_kho'],
            'con_cat' => (float) ($orderRows->con_cat ?? 0),
            'con_giao' => (float) ($orderRows->con_giao ?? 0),
            'dong_thieu_cat' => (int) ($orderRows->dong_thieu_cat ?? 0),
            'dong_thieu_hang_kho' => (int) ($orderRows->dong_thieu_hang_kho ?? 0),
        ];
    }

    private function getCumulativeProductionSummary(array $filters): array
    {
        $from = '1000-01-01';
        $to = now()->toDateString();

        return [
            'da_cat' => $this->productionSum('cat', $from, $to, $filters),
            'da_giao_may' => $this->productionSum('phan_bo_may', $from, $to, $filters),
            'qc_dat' => $this->productionSum('qc_dat', $from, $to, $filters),
            'qc_loi' => $this->productionSum('qc_loi', $from, $to, $filters),
            'nhap_kho' => $this->productionSum('nhap_kho', $from, $to, $filters),
            'da_xuat' => $this->productionSum('xuat_hang', $from, $to, $filters),
            'ton_kho' => $this->inventoryBalanceAt($to, $filters),
        ];
    }

    public function getTimeProductionSummary(array $filters = []): array
    {
        [$from, $to] = $this->dateRange($filters);

        $cat = $this->productionSum('cat', $from, $to, $filters);
        $giaoMay = $this->productionSum('phan_bo_may', $from, $to, $filters);
        $qcDat = $this->productionSum('qc_dat', $from, $to, $filters);
        $qcLoi = $this->productionSum('qc_loi', $from, $to, $filters);
        $nhapKho = $this->productionSum('nhap_kho', $from, $to, $filters);
        $xuatHang = $this->productionSum('xuat_hang', $from, $to, $filters);
        $tonCuoiKy = $this->inventoryBalanceAt($to, $filters);

        return [
            'date_from' => $from,
            'date_to' => $to,
            'da_cat' => $cat,
            'da_giao_may' => $giaoMay,
            'qc_dat' => $qcDat,
            'qc_loi' => $qcLoi,
            'nhap_kho' => $nhapKho,
            'da_xuat' => $xuatHang,
            'ton_kho' => $tonCuoiKy,
        ];
    }

    public function getTodayProduction(): array
    {
        $today = now()->toDateString();

        return [
            'cat' => $this->productionSum('cat', $today, $today, []),
            'giao_may' => $this->productionSum('phan_bo_may', $today, $today, []),
            'qc_dat' => $this->productionSum('qc_dat', $today, $today, []),
            'qc_loi' => $this->productionSum('qc_loi', $today, $today, []),
            'nhap_kho' => $this->productionSum('nhap_kho', $today, $today, []),
            'xuat_hang' => $this->productionSum('xuat_hang', $today, $today, []),
        ];
    }

    public function getDailyProduction(array $filters = []): Collection
    {
        [$from, $to] = $this->dateRange($filters);
        $dates = collect(CarbonPeriod::create($from, $to))
            ->map(fn (Carbon $date) => $date->toDateString())
            ->sortDesc()
            ->values();

        $cat = $this->dailyProductionTotals('cat', $from, $to);
        $giaoMay = $this->dailyProductionTotals('phan_bo_may', $from, $to);
        $qcDat = $this->dailyProductionTotals('qc_dat', $from, $to);
        $qcLoi = $this->dailyProductionTotals('qc_loi', $from, $to);
        $nhapKho = $this->dailyProductionTotals('nhap_kho', $from, $to);
        $xuatHang = $this->dailyProductionTotals('xuat_hang', $from, $to);

        return $dates->map(fn (string $date) => [
            'date' => $date,
            'cat' => (float) ($cat[$date] ?? 0),
            'giao_may' => (float) ($giaoMay[$date] ?? 0),
            'qc_dat' => (float) ($qcDat[$date] ?? 0),
            'qc_loi' => (float) ($qcLoi[$date] ?? 0),
            'nhap_kho' => (float) ($nhapKho[$date] ?? 0),
            'xuat_hang' => (float) ($xuatHang[$date] ?? 0),
        ]);
    }

    private function orderLineProgressQuery(array $filters = []): Builder
    {
        $donHangTable = (new DonHang)->getTable();
        $donHangChiTietTable = (new DonHangChiTiet)->getTable();

        $catTotals = DB::table('cat')
            ->selectRaw('cat.don_hang_chi_tiet_id, COALESCE(SUM(cat.so_luong_cat), 0) as da_cat')
            ->whereNotNull('cat.don_hang_chi_tiet_id')
            ->whereNull('cat.deleted_at')
            ->groupBy('cat.don_hang_chi_tiet_id');

        $phanBoTotals = DB::table('phan_bo_may as pbm')
            ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->selectRaw('COALESCE(pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id, COALESCE(SUM(pbm.so_luong_giao), 0) as da_giao_may')
            ->whereRaw('COALESCE(pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) is not null')
            ->whereNull('pbm.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
            ->groupByRaw('COALESCE(pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)');

        $qcTotals = DB::table('qc')
            ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
            ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->selectRaw('
                COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id,
                COALESCE(SUM(qc.so_luong_dat), 0) as qc_dat,
                COALESCE(SUM(qc.so_luong_loi), 0) + COALESCE(SUM(qc.so_luong_hong), 0) as qc_loi
            ')
            ->whereRaw('COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) is not null')
            ->whereNull('qc.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
            ->groupByRaw('COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)');

        $nhapTotals = DB::table('nhap_kho as nk')
            ->leftJoin('qc', 'qc.id', '=', 'nk.qc_id')
            ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
            ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->selectRaw("
                COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id,
                COALESCE(SUM(nk.so_luong_nhap), 0) as nhap_kho,
                COALESCE(SUM(CASE WHEN COALESCE(nk.loai_ton, 'dat') = 'dat' THEN nk.so_luong_nhap ELSE 0 END), 0) as nhap_dat
            ")
            ->whereRaw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) is not null')
            ->whereNull('nk.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('qc.id')->orWhereNull('qc.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
            ->groupByRaw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)');

        $xuatTotals = DB::table('phieu_xuat_kho_chi_tiet as pxct')
            ->leftJoin('phieu_xuat_kho as px', 'px.id', '=', 'pxct.phieu_xuat_kho_id')
            ->leftJoin('nhap_kho as nk', 'nk.id', '=', 'pxct.nhap_kho_id')
            ->leftJoin('qc', 'qc.id', '=', 'nk.qc_id')
            ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
            ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->selectRaw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id, COALESCE(SUM(pxct.so_luong_xuat), 0) as da_xuat')
            ->whereRaw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) is not null')
            ->whereNull('pxct.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('px.id')->orWhereNull('px.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('nk.id')->orWhereNull('nk.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('qc.id')->orWhereNull('qc.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
            ->groupByRaw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)');

        return DB::table($donHangChiTietTable.' as dct')
            ->join($donHangTable.' as dh', 'dh.id', '=', 'dct.don_hang_id')
            ->leftJoinSub($catTotals, 'cat_totals', fn ($join) => $join->on('dct.id', '=', 'cat_totals.don_hang_chi_tiet_id'))
            ->leftJoinSub($phanBoTotals, 'phan_bo_totals', fn ($join) => $join->on('dct.id', '=', 'phan_bo_totals.don_hang_chi_tiet_id'))
            ->leftJoinSub($qcTotals, 'qc_totals', fn ($join) => $join->on('dct.id', '=', 'qc_totals.don_hang_chi_tiet_id'))
            ->leftJoinSub($nhapTotals, 'nhap_totals', fn ($join) => $join->on('dct.id', '=', 'nhap_totals.don_hang_chi_tiet_id'))
            ->leftJoinSub($xuatTotals, 'xuat_totals', fn ($join) => $join->on('dct.id', '=', 'xuat_totals.don_hang_chi_tiet_id'))
            ->whereNull('dct.deleted_at')
            ->whereNull('dh.deleted_at')
            ->when(trim((string) ($filters['ma_don'] ?? '')) !== '', fn (Builder $query) => $query->where('dh.ma_don', 'like', '%'.trim((string) $filters['ma_don']).'%'))
            ->when(trim((string) ($filters['ma_kh'] ?? '')) !== '', fn (Builder $query) => $query->where('dh.ma_kh', 'like', '%'.trim((string) $filters['ma_kh']).'%'))
            ->when(filled($filters['mat_hang_id'] ?? null), fn (Builder $query) => $query->where('dct.mat_hang_id', (int) $filters['mat_hang_id']))
            ->when(filled($filters['mau_id'] ?? null), fn (Builder $query) => $query->where('dct.mau_id', (int) $filters['mau_id']))
            ->when(filled($filters['size_id'] ?? null), fn (Builder $query) => $query->where('dct.size_id', (int) $filters['size_id']))
            ->when(trim((string) ($filters['kenh_ban'] ?? '')) !== '', fn (Builder $query) => $query->where('dh.kenh_ban', trim((string) $filters['kenh_ban'])))
            ->when(filled($filters['ngay_nhan_tu'] ?? null), fn (Builder $query) => $query->whereDate('dh.ngay_nhan', '>=', $filters['ngay_nhan_tu']))
            ->when(filled($filters['ngay_nhan_den'] ?? null), fn (Builder $query) => $query->whereDate('dh.ngay_nhan', '<=', $filters['ngay_nhan_den']))
            ->when(filled($filters['han_giao_tu'] ?? null), fn (Builder $query) => $query->whereDate('dh.han_giao', '>=', $filters['han_giao_tu']))
            ->when(filled($filters['han_giao_den'] ?? null), fn (Builder $query) => $query->whereDate('dh.han_giao', '<=', $filters['han_giao_den']))
            ->selectRaw('
                dct.id as don_hang_chi_tiet_id,
                dct.so_luong_dat,
                COALESCE(cat_totals.da_cat, 0) as da_cat,
                COALESCE(phan_bo_totals.da_giao_may, 0) as da_giao_may,
                COALESCE(qc_totals.qc_dat, 0) as qc_dat,
                COALESCE(qc_totals.qc_loi, 0) as qc_loi,
                COALESCE(nhap_totals.nhap_kho, 0) as nhap_kho,
                COALESCE(nhap_totals.nhap_dat, 0) as nhap_dat,
                COALESCE(xuat_totals.da_xuat, 0) as da_xuat,
                (COALESCE(nhap_totals.nhap_kho, 0) - COALESCE(xuat_totals.da_xuat, 0)) as ton_kho,
                (dct.so_luong_dat - COALESCE(cat_totals.da_cat, 0)) as con_cat,
                (dct.so_luong_dat - COALESCE(phan_bo_totals.da_giao_may, 0)) as con_giao
            ');
    }

    private function productionSum(string $module, string $from, string $to, array $filters): float
    {
        $query = match ($module) {
            'cat' => DB::table('cat as c')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', 'c.don_hang_chi_tiet_id')
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('c.ngay_cat', [$from, $to])
                ->whereNull('c.deleted_at')
                ->selectRaw('COALESCE(SUM(c.so_luong_cat), 0) as total'),
            'phan_bo_may' => DB::table('phan_bo_may as pbm')
                ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('pbm.ngay_phan_bo', [$from, $to])
                ->whereNull('pbm.deleted_at')
                ->whereNull('c.deleted_at')
                ->selectRaw('COALESCE(SUM(pbm.so_luong_giao), 0) as total'),
            'qc_dat' => DB::table('qc')
                ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('qc.ngay_qc', [$from, $to])
                ->whereNull('qc.deleted_at')
                ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
                ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
                ->selectRaw('COALESCE(SUM(qc.so_luong_dat), 0) as total'),
            'qc_loi' => DB::table('qc')
                ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('qc.ngay_qc', [$from, $to])
                ->whereNull('qc.deleted_at')
                ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
                ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
                ->selectRaw('COALESCE(SUM(qc.so_luong_loi), 0) + COALESCE(SUM(qc.so_luong_hong), 0) as total'),
            'nhap_kho' => DB::table('nhap_kho as nk')
                ->join('qc', 'qc.id', '=', 'nk.qc_id')
                ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('nk.ngay_nhap', [$from, $to])
                ->whereNull('nk.deleted_at')
                ->whereNull('qc.deleted_at')
                ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
                ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
                ->selectRaw('COALESCE(SUM(nk.so_luong_nhap), 0) as total'),
            'xuat_hang' => DB::table('phieu_xuat_kho_chi_tiet as pxct')
                ->join('phieu_xuat_kho as px', 'px.id', '=', 'pxct.phieu_xuat_kho_id')
                ->join('nhap_kho as nk', 'nk.id', '=', 'pxct.nhap_kho_id')
                ->join('qc', 'qc.id', '=', 'nk.qc_id')
                ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('px.ngay_xuat', [$from, $to])
                ->whereNull('pxct.deleted_at')
                ->whereNull('px.deleted_at')
                ->whereNull('nk.deleted_at')
                ->whereNull('qc.deleted_at')
                ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
                ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
                ->selectRaw('COALESCE(SUM(pxct.so_luong_xuat), 0) as total'),
        };

        $this->applyProductionFilters(
            $query,
            $filters,
            in_array($module, ['qc_dat', 'qc_loi', 'nhap_kho', 'xuat_hang'], true)
        );

        return (float) ($query->first()->total ?? 0);
    }

    private function applyProductionFilters(Builder $query, array $filters, bool $supportsManualQc = false): void
    {
        $maDon = trim((string) ($filters['ma_don'] ?? ''));
        $maKh = trim((string) ($filters['ma_kh'] ?? ''));
        $kenhBan = trim((string) ($filters['kenh_ban'] ?? ''));
        $matHangColumn = $supportsManualQc ? 'COALESCE(c.mat_hang_id, qc.mat_hang_id)' : 'c.mat_hang_id';
        $mauColumn = $supportsManualQc ? 'COALESCE(c.mau_id, qc.mau_id)' : 'c.mau_id';
        $sizeColumn = $supportsManualQc ? 'COALESCE(c.size_id, qc.size_id)' : 'c.size_id';

        $query
            ->where(fn (Builder $query) => $query->whereNull('dct.id')->orWhereNull('dct.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('dh.id')->orWhereNull('dh.deleted_at'))
            ->when($maDon !== '', fn (Builder $query) => $query->where('dh.ma_don', 'like', "%{$maDon}%"))
            ->when($maKh !== '', fn (Builder $query) => $query->where('dh.ma_kh', 'like', "%{$maKh}%"))
            ->when($kenhBan !== '', fn (Builder $query) => $query->where('dh.kenh_ban', $kenhBan))
            ->when(filled($filters['ngay_nhan_tu'] ?? null), fn (Builder $query) => $query->whereDate('dh.ngay_nhan', '>=', $filters['ngay_nhan_tu']))
            ->when(filled($filters['ngay_nhan_den'] ?? null), fn (Builder $query) => $query->whereDate('dh.ngay_nhan', '<=', $filters['ngay_nhan_den']))
            ->when(filled($filters['han_giao_tu'] ?? null), fn (Builder $query) => $query->whereDate('dh.han_giao', '>=', $filters['han_giao_tu']))
            ->when(filled($filters['han_giao_den'] ?? null), fn (Builder $query) => $query->whereDate('dh.han_giao', '<=', $filters['han_giao_den']))
            ->when(filled($filters['mat_hang_id'] ?? null), fn (Builder $query) => $query->whereRaw($matHangColumn.' = ?', [(int) $filters['mat_hang_id']]))
            ->when(filled($filters['mau_id'] ?? null), fn (Builder $query) => $query->whereRaw($mauColumn.' = ?', [(int) $filters['mau_id']]))
            ->when(filled($filters['size_id'] ?? null), fn (Builder $query) => $query->whereRaw($sizeColumn.' = ?', [(int) $filters['size_id']]));
    }

    private function inventoryBalanceAt(string $to, array $filters): float
    {
        $nhapKho = DB::table('nhap_kho as nk')
            ->join('qc', 'qc.id', '=', 'nk.qc_id')
            ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
            ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
            ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
            ->whereDate('nk.ngay_nhap', '<=', $to)
            ->whereNull('nk.deleted_at')
            ->whereNull('qc.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
            ->selectRaw('COALESCE(SUM(nk.so_luong_nhap), 0) as total');

        $xuatKho = DB::table('phieu_xuat_kho_chi_tiet as pxct')
            ->join('phieu_xuat_kho as px', 'px.id', '=', 'pxct.phieu_xuat_kho_id')
            ->join('nhap_kho as nk', 'nk.id', '=', 'pxct.nhap_kho_id')
            ->join('qc', 'qc.id', '=', 'nk.qc_id')
            ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
            ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
            ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
            ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
            ->whereDate('px.ngay_xuat', '<=', $to)
            ->whereNull('pxct.deleted_at')
            ->whereNull('px.deleted_at')
            ->whereNull('nk.deleted_at')
            ->whereNull('qc.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at'))
            ->selectRaw('COALESCE(SUM(pxct.so_luong_xuat), 0) as total');

        $this->applyProductionFilters($nhapKho, $filters, true);
        $this->applyProductionFilters($xuatKho, $filters, true);

        return (float) ($nhapKho->first()->total ?? 0) - (float) ($xuatKho->first()->total ?? 0);
    }

    private function dateRange(array $filters): array
    {
        if (filled($filters['month'] ?? null)) {
            $month = Carbon::createFromFormat('Y-m', (string) $filters['month'])->startOfMonth();
            $maxWeek = (int) ceil($month->daysInMonth / 7);
            $week = max(1, min((int) ($filters['week'] ?? 1), $maxWeek));
            $from = $month->copy()->addDays(($week - 1) * 7);
            $to = $from->copy()->addDays(6)->min($month->copy()->endOfMonth());

            if ($month->isSameMonth(now())) {
                $to = $to->min(now()->startOfDay());
            }

            return [$from->toDateString(), $to->toDateString()];
        }

        $to = filled($filters['date_to'] ?? null)
            ? Carbon::parse($filters['date_to'])->startOfDay()
            : now()->startOfDay();

        $from = filled($filters['date_from'] ?? null)
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : $to->copy()->subDays(6);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    private function dailyProductionTotals(string $module, string $from, string $to): Collection
    {
        $query = match ($module) {
            'cat' => DB::table('cat as c')
                ->selectRaw('DATE(c.ngay_cat) as production_date, COALESCE(SUM(c.so_luong_cat), 0) as total')
                ->whereBetween('c.ngay_cat', [$from, $to])
                ->whereNull('c.deleted_at'),
            'phan_bo_may' => DB::table('phan_bo_may as pbm')
                ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->selectRaw('DATE(pbm.ngay_phan_bo) as production_date, COALESCE(SUM(pbm.so_luong_giao), 0) as total')
                ->whereBetween('pbm.ngay_phan_bo', [$from, $to])
                ->whereNull('pbm.deleted_at')
                ->whereNull('c.deleted_at'),
            'qc_dat' => DB::table('qc')
                ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->selectRaw('DATE(qc.ngay_qc) as production_date, COALESCE(SUM(qc.so_luong_dat), 0) as total')
                ->whereBetween('qc.ngay_qc', [$from, $to])
                ->whereNull('qc.deleted_at')
                ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
                ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at')),
            'qc_loi' => DB::table('qc')
                ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->selectRaw('DATE(qc.ngay_qc) as production_date, COALESCE(SUM(qc.so_luong_loi), 0) + COALESCE(SUM(qc.so_luong_hong), 0) as total')
                ->whereBetween('qc.ngay_qc', [$from, $to])
                ->whereNull('qc.deleted_at')
                ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
                ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at')),
            'nhap_kho' => DB::table('nhap_kho as nk')
                ->join('qc', 'qc.id', '=', 'nk.qc_id')
                ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->selectRaw('DATE(nk.ngay_nhap) as production_date, COALESCE(SUM(nk.so_luong_nhap), 0) as total')
                ->whereBetween('nk.ngay_nhap', [$from, $to])
                ->whereNull('nk.deleted_at')
                ->whereNull('qc.deleted_at')
                ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
                ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at')),
            'xuat_hang' => DB::table('phieu_xuat_kho_chi_tiet as pxct')
                ->join('phieu_xuat_kho as px', 'px.id', '=', 'pxct.phieu_xuat_kho_id')
                ->join('nhap_kho as nk', 'nk.id', '=', 'pxct.nhap_kho_id')
                ->join('qc', 'qc.id', '=', 'nk.qc_id')
                ->leftJoin('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->leftJoin('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->selectRaw('DATE(px.ngay_xuat) as production_date, COALESCE(SUM(pxct.so_luong_xuat), 0) as total')
                ->whereBetween('px.ngay_xuat', [$from, $to])
                ->whereNull('pxct.deleted_at')
                ->whereNull('px.deleted_at')
                ->whereNull('nk.deleted_at')
                ->whereNull('qc.deleted_at')
                ->where(fn (Builder $query) => $query->whereNull('pbm.id')->orWhereNull('pbm.deleted_at'))
                ->where(fn (Builder $query) => $query->whereNull('c.id')->orWhereNull('c.deleted_at')),
        };

        return $query
            ->groupByRaw('production_date')
            ->pluck('total', 'production_date');
    }
}
