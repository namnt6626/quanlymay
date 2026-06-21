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
        $rows = DB::query()
            ->fromSub($this->orderLineProgressQuery($filters), 'progress')
            ->selectRaw('
                COALESCE(SUM(so_luong_dat), 0) as tong_sl_dat,
                COALESCE(SUM(da_cat), 0) as da_cat,
                COALESCE(SUM(da_giao_may), 0) as da_giao_may,
                COALESCE(SUM(qc_dat), 0) as qc_dat,
                COALESCE(SUM(qc_loi), 0) as qc_loi,
                COALESCE(SUM(nhap_kho), 0) as nhap_kho,
                COALESCE(SUM(da_xuat), 0) as da_xuat,
                COALESCE(SUM(ton_kho), 0) as ton_kho,
                COALESCE(SUM(con_cat), 0) as con_cat,
                COALESCE(SUM(con_giao), 0) as con_giao,
                COALESCE(SUM(CASE WHEN da_cat < so_luong_dat THEN 1 ELSE 0 END), 0) as dong_thieu_cat,
                COALESCE(SUM(CASE WHEN nhap_kho < so_luong_dat THEN 1 ELSE 0 END), 0) as dong_thieu_hang_kho
            ')
            ->first();

        return [
            'tong_sl_dat' => (float) ($rows->tong_sl_dat ?? 0),
            'da_cat' => (float) ($rows->da_cat ?? 0),
            'da_giao_may' => (float) ($rows->da_giao_may ?? 0),
            'qc_dat' => (float) ($rows->qc_dat ?? 0),
            'qc_loi' => (float) ($rows->qc_loi ?? 0),
            'nhap_kho' => (float) ($rows->nhap_kho ?? 0),
            'da_xuat' => (float) ($rows->da_xuat ?? 0),
            'ton_kho' => (float) ($rows->ton_kho ?? 0),
            'con_cat' => (float) ($rows->con_cat ?? 0),
            'con_giao' => (float) ($rows->con_giao ?? 0),
            'dong_thieu_cat' => (int) ($rows->dong_thieu_cat ?? 0),
            'dong_thieu_hang_kho' => (int) ($rows->dong_thieu_hang_kho ?? 0),
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

        return [
            'date_from' => $from,
            'date_to' => $to,
            'da_cat' => $cat,
            'da_giao_may' => $giaoMay,
            'qc_dat' => $qcDat,
            'qc_loi' => $qcLoi,
            'nhap_kho' => $nhapKho,
            'da_xuat' => $xuatHang,
            'ton_kho' => $nhapKho - $xuatHang,
        ];
    }

    public function getTodayProduction(): array
    {
        $today = now()->toDateString();

        return [
            'cat' => $this->sumByDate('cat', 'ngay_cat', 'so_luong_cat', $today),
            'giao_may' => $this->sumByDate('phan_bo_may', 'ngay_phan_bo', 'so_luong_giao', $today),
            'qc_dat' => $this->sumByDate('qc', 'ngay_qc', 'so_luong_dat', $today),
            'qc_loi' => $this->sumQcErrorByDate($today),
            'nhap_kho' => $this->sumByDate('nhap_kho', 'ngay_nhap', 'so_luong_nhap', $today),
            'xuat_hang' => $this->sumXuatByDate($today),
        ];
    }

    public function getDailyProduction(array $filters = []): Collection
    {
        [$from, $to] = $this->dateRange($filters);
        $dates = collect(CarbonPeriod::create($from, $to))
            ->map(fn (Carbon $date) => $date->toDateString());

        $cat = $this->dailyTotals('cat', 'ngay_cat', 'so_luong_cat', $from, $to);
        $giaoMay = $this->dailyTotals('phan_bo_may', 'ngay_phan_bo', 'so_luong_giao', $from, $to);
        $qcDat = $this->dailyTotals('qc', 'ngay_qc', 'so_luong_dat', $from, $to);
        $qcLoi = $this->dailyQcErrorTotals($from, $to);
        $nhapKho = $this->dailyTotals('nhap_kho', 'ngay_nhap', 'so_luong_nhap', $from, $to);
        $xuatHang = $this->dailyXuatTotals($from, $to);

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
            ->selectRaw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id) as don_hang_chi_tiet_id, COALESCE(SUM(nk.so_luong_nhap), 0) as nhap_kho')
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
                ->join('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('qc.ngay_qc', [$from, $to])
                ->whereNull('qc.deleted_at')
                ->whereNull('pbm.deleted_at')
                ->whereNull('c.deleted_at')
                ->selectRaw('COALESCE(SUM(qc.so_luong_dat), 0) as total'),
            'qc_loi' => DB::table('qc')
                ->join('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('qc.ngay_qc', [$from, $to])
                ->whereNull('qc.deleted_at')
                ->whereNull('pbm.deleted_at')
                ->whereNull('c.deleted_at')
                ->selectRaw('COALESCE(SUM(qc.so_luong_loi), 0) + COALESCE(SUM(qc.so_luong_hong), 0) as total'),
            'nhap_kho' => DB::table('nhap_kho as nk')
                ->join('qc', 'qc.id', '=', 'nk.qc_id')
                ->join('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('nk.ngay_nhap', [$from, $to])
                ->whereNull('nk.deleted_at')
                ->whereNull('qc.deleted_at')
                ->whereNull('pbm.deleted_at')
                ->whereNull('c.deleted_at')
                ->selectRaw('COALESCE(SUM(nk.so_luong_nhap), 0) as total'),
            'xuat_hang' => DB::table('phieu_xuat_kho_chi_tiet as pxct')
                ->join('phieu_xuat_kho as px', 'px.id', '=', 'pxct.phieu_xuat_kho_id')
                ->join('nhap_kho as nk', 'nk.id', '=', 'pxct.nhap_kho_id')
                ->join('qc', 'qc.id', '=', 'nk.qc_id')
                ->join('phan_bo_may as pbm', 'pbm.id', '=', 'qc.phan_bo_may_id')
                ->join('cat as c', 'c.id', '=', 'pbm.cat_id')
                ->leftJoin('don_hang_chi_tiets as dct', 'dct.id', '=', DB::raw('COALESCE(pxct.don_hang_chi_tiet_id, nk.don_hang_chi_tiet_id, qc.don_hang_chi_tiet_id, pbm.don_hang_chi_tiet_id, c.don_hang_chi_tiet_id)'))
                ->leftJoin('don_hangs as dh', 'dh.id', '=', 'dct.don_hang_id')
                ->whereBetween('px.ngay_xuat', [$from, $to])
                ->whereNull('pxct.deleted_at')
                ->whereNull('px.deleted_at')
                ->whereNull('nk.deleted_at')
                ->whereNull('qc.deleted_at')
                ->whereNull('pbm.deleted_at')
                ->whereNull('c.deleted_at')
                ->selectRaw('COALESCE(SUM(pxct.so_luong_xuat), 0) as total'),
        };

        $this->applyProductionFilters($query, $filters);

        return (float) ($query->first()->total ?? 0);
    }

    private function applyProductionFilters(Builder $query, array $filters): void
    {
        $maDon = trim((string) ($filters['ma_don'] ?? ''));
        $maKh = trim((string) ($filters['ma_kh'] ?? ''));

        $query
            ->where(fn (Builder $query) => $query->whereNull('dct.id')->orWhereNull('dct.deleted_at'))
            ->where(fn (Builder $query) => $query->whereNull('dh.id')->orWhereNull('dh.deleted_at'))
            ->when($maDon !== '', fn (Builder $query) => $query->where('dh.ma_don', 'like', "%{$maDon}%"))
            ->when($maKh !== '', fn (Builder $query) => $query->where('dh.ma_kh', 'like', "%{$maKh}%"))
            ->when(filled($filters['mat_hang_id'] ?? null), fn (Builder $query) => $query->where('c.mat_hang_id', (int) $filters['mat_hang_id']))
            ->when(filled($filters['mau_id'] ?? null), fn (Builder $query) => $query->where('c.mau_id', (int) $filters['mau_id']))
            ->when(filled($filters['size_id'] ?? null), fn (Builder $query) => $query->where('c.size_id', (int) $filters['size_id']));
    }

    private function dateRange(array $filters): array
    {
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

    private function sumByDate(string $table, string $dateColumn, string $quantityColumn, string $date): float
    {
        return (float) DB::table($table)
            ->whereDate($dateColumn, $date)
            ->whereNull('deleted_at')
            ->sum($quantityColumn);
    }

    private function sumQcErrorByDate(string $date): float
    {
        $row = DB::table('qc')
            ->whereDate('ngay_qc', $date)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(so_luong_loi), 0) + COALESCE(SUM(so_luong_hong), 0) as total')
            ->first();

        return (float) ($row->total ?? 0);
    }

    private function sumXuatByDate(string $date): float
    {
        return (float) DB::table('phieu_xuat_kho_chi_tiet as pxct')
            ->join('phieu_xuat_kho as px', 'px.id', '=', 'pxct.phieu_xuat_kho_id')
            ->whereDate('px.ngay_xuat', $date)
            ->whereNull('pxct.deleted_at')
            ->whereNull('px.deleted_at')
            ->sum('pxct.so_luong_xuat');
    }

    private function dailyTotals(string $table, string $dateColumn, string $quantityColumn, string $from, string $to): Collection
    {
        return DB::table($table)
            ->selectRaw("DATE({$dateColumn}) as production_date, COALESCE(SUM({$quantityColumn}), 0) as total")
            ->whereBetween($dateColumn, [$from, $to])
            ->whereNull('deleted_at')
            ->groupByRaw("DATE({$dateColumn})")
            ->pluck('total', 'production_date');
    }

    private function dailyQcErrorTotals(string $from, string $to): Collection
    {
        return DB::table('qc')
            ->selectRaw('DATE(ngay_qc) as production_date, COALESCE(SUM(so_luong_loi), 0) + COALESCE(SUM(so_luong_hong), 0) as total')
            ->whereBetween('ngay_qc', [$from, $to])
            ->whereNull('deleted_at')
            ->groupByRaw('DATE(ngay_qc)')
            ->pluck('total', 'production_date');
    }

    private function dailyXuatTotals(string $from, string $to): Collection
    {
        return DB::table('phieu_xuat_kho_chi_tiet as pxct')
            ->join('phieu_xuat_kho as px', 'px.id', '=', 'pxct.phieu_xuat_kho_id')
            ->selectRaw('DATE(px.ngay_xuat) as production_date, COALESCE(SUM(pxct.so_luong_xuat), 0) as total')
            ->whereBetween('px.ngay_xuat', [$from, $to])
            ->whereNull('pxct.deleted_at')
            ->whereNull('px.deleted_at')
            ->groupByRaw('DATE(px.ngay_xuat)')
            ->pluck('total', 'production_date');
    }
}
