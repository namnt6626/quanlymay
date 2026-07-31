<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\DmKenhBan;
use App\Models\DonHangChiTiet;
use App\Services\BaoCao\BaoCaoTongHopDonHangService;
use App\Services\Dashboard\DashboardService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Analytics extends Controller
{
    public function index(Request $request, DashboardService $service, BaoCaoTongHopDonHangService $orderReportService): View
    {
        $quickFilters = [
            'ma_don' => trim((string) $request->input('quick_ma_don')),
            'ma_kh' => trim((string) $request->input('quick_ma_kh')),
            'mat_hang_id' => $request->integer('quick_mat_hang_id') ?: null,
            'mau_id' => $request->integer('quick_mau_id') ?: null,
            'size_id' => $request->integer('quick_size_id') ?: null,
            'kenh_ban' => trim((string) $request->input('quick_kenh_ban')),
            'ngay_nhan_tu' => trim((string) $request->input('quick_ngay_nhan_tu')),
            'ngay_nhan_den' => trim((string) $request->input('quick_ngay_nhan_den')),
            'han_giao_tu' => trim((string) $request->input('quick_han_giao_tu')),
            'han_giao_den' => trim((string) $request->input('quick_han_giao_den')),
        ];

        $selectedMonth = trim((string) $request->input('time_month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $monthDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        if ($monthDate->gt(now()->startOfMonth())) {
            $monthDate = now()->startOfMonth();
            $selectedMonth = $monthDate->format('Y-m');
        }

        $lastAvailableDay = $monthDate->isSameMonth(now())
            ? now()->day
            : $monthDate->daysInMonth;
        $maxWeekOfMonth = (int) ceil($lastAvailableDay / 7);
        $selectedWeek = max(1, min($request->integer('time_week') ?: (int) ceil(now()->day / 7), $maxWeekOfMonth));

        $timeFilters = [
            'month' => $selectedMonth,
            'week' => $selectedWeek,
            'ma_don' => trim((string) $request->input('time_ma_don')),
            'ma_kh' => trim((string) $request->input('time_ma_kh')),
            'mat_hang_id' => $request->integer('time_mat_hang_id') ?: null,
            'mau_id' => $request->integer('time_mau_id') ?: null,
            'size_id' => $request->integer('time_size_id') ?: null,
        ];

        $dailyFilters = [
            'date_from' => trim((string) $request->input('daily_date_from')),
            'date_to' => trim((string) $request->input('daily_date_to')),
        ];
        $dailyPerPage = in_array($request->integer('daily_per_page'), paginationPerPageOptions(), true)
            ? $request->integer('daily_per_page')
            : 10;
        $dailyRows = $service->getDailyProduction($dailyFilters);
        $dailyPage = max(1, $request->integer('daily_page', 1));
        $dailyProduction = new LengthAwarePaginator(
            $dailyRows->forPage($dailyPage, $dailyPerPage)->values(),
            $dailyRows->count(),
            $dailyPerPage,
            $dailyPage,
            [
                'path' => $request->url(),
                'pageName' => 'daily_page',
                'query' => $request->query(),
            ]
        );

        $onlineReport = $this->onlineSalesReport($request);
        $orderReport = $this->orderSummaryReport($request, $orderReportService);

        return view('content.dashboard.dashboards-analytics', [
            'quickFilters' => $quickFilters,
            'timeFilters' => $timeFilters,
            'dailyFilters' => $dailyFilters,
            ...$onlineReport,
            ...$orderReport,
            'maxWeekOfMonth' => $maxWeekOfMonth,
            'quickSummary' => $service->getQuickSummary($quickFilters),
            'timeProductionSummary' => $service->getTimeProductionSummary($timeFilters),
            'todayProduction' => $service->getTodayProduction(),
            'dailyProduction' => $dailyProduction,
            'dailyPerPage' => $dailyPerPage,
            ...$this->filterOptions(),
        ]);
    }

    private function onlineSalesReport(Request $request): array
    {
        $defaultDateRange = $this->onlineSalesDateRange();
        $onlineFilters = [
            'tu_ngay' => $this->dateInput($request, 'online_tu_ngay', $defaultDateRange['tu_ngay']),
            'den_ngay' => $this->dateInput($request, 'online_den_ngay', $defaultDateRange['den_ngay']),
            'ma_hang' => trim((string) $request->input('online_ma_hang')),
            'mau' => trim((string) $request->input('online_mau')),
            'size' => trim((string) $request->input('online_size')),
            'kenh_ban' => DmKenhBan::activeNames()->contains($request->input('online_kenh_ban')) ? $request->input('online_kenh_ban') : '',
            'per_page' => in_array($request->integer('online_per_page'), paginationPerPageOptions(), true)
                ? $request->integer('online_per_page')
                : paginationPerPage(),
        ];

        if (Carbon::parse($onlineFilters['tu_ngay'])->gt(Carbon::parse($onlineFilters['den_ngay']))) {
            $onlineFilters['tu_ngay'] = $defaultDateRange['tu_ngay'];
            $onlineFilters['den_ngay'] = $defaultDateRange['den_ngay'];
        }

        $base = $this->onlineSalesBaseQuery($onlineFilters);
        $onlineTotals = (clone $base)->selectRaw('
            COALESCE(SUM(ct.thanh_tien), 0) as tong_tien_ban_hang,
            COUNT(DISTINCT dhht.ngay_hoan_thanh) as so_ngay_ban
        ')->first();
        $onlineTotals->tong_so_luong = $this->onlineSoldQuantity($onlineFilters);
        $onlineReturnBase = $this->onlineReturnsBaseQuery($onlineFilters);
        $onlineReturnTotals = (clone $onlineReturnBase)->selectRaw('
            COUNT(DISTINCT hhct.order_id) as so_don_hoan,
            COALESCE(SUM(hhct.so_luong_hoan), 0) as so_luong_hoan
        ')->first();

        $from = Carbon::parse($onlineFilters['tu_ngay']);
        $to = Carbon::parse($onlineFilters['den_ngay']);
        $days = (int) $from->diffInDays($to) + 1;
        $previousFilters = [...$onlineFilters,
            'tu_ngay' => $from->copy()->subDays($days)->toDateString(),
            'den_ngay' => $from->copy()->subDay()->toDateString(),
        ];

        $previousRevenue = (float) $this->onlineSalesBaseQuery($previousFilters)->sum('ct.thanh_tien');
        $currentRevenue = (float) ($onlineTotals->tong_tien_ban_hang ?? 0);
        $onlineRevenueChange = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : null;

        $onlineTrend = (clone $base)
            ->selectRaw('dhht.ngay_hoan_thanh as ngay, SUM(ct.so_luong) as so_luong, SUM(ct.thanh_tien) as tong_tien')
            ->groupBy('dhht.ngay_hoan_thanh')
            ->orderBy('dhht.ngay_hoan_thanh')
            ->get();
        $onlineDailyPage = max(1, $request->integer('online_daily_page', 1));
        $onlineDailyRows = new LengthAwarePaginator(
            $onlineTrend->forPage($onlineDailyPage, $onlineFilters['per_page'])->values(),
            $onlineTrend->count(),
            $onlineFilters['per_page'],
            $onlineDailyPage,
            [
                'path' => $request->url(),
                'pageName' => 'online_daily_page',
                'query' => $request->query(),
            ]
        );

        $onlineProductName = $this->onlineProductNameExpression();
        $onlineColorName = $this->onlineColorNameExpression();

        $onlineTopProducts = (clone $base)
            ->selectRaw($onlineProductName.' as ten_san_pham, SUM(ct.so_luong) as so_luong, SUM(ct.thanh_tien) as tong_tien')
            ->groupByRaw($onlineProductName)
            ->orderByDesc('so_luong')
            ->limit(10)
            ->get();

        $onlineTopReturnProducts = (clone $onlineReturnBase)
            ->selectRaw('COALESCE(opa.group_name, hhct.ten_san_pham) as ten_san_pham, SUM(hhct.so_luong_hoan) as so_luong_hoan')
            ->groupByRaw('COALESCE(opa.group_name, hhct.ten_san_pham)')
            ->orderByDesc('so_luong_hoan')
            ->limit(10)
            ->get();

        $onlineReturnReasonsBase = (clone $onlineReturnBase)
            ->selectRaw("COALESCE(NULLIF(hhct.return_reason, ''), 'Không rõ') as return_reason, hhct.so_luong_hoan");
        $onlineTopReturnReasons = DB::query()
            ->fromSub($onlineReturnReasonsBase, 'return_reasons')
            ->selectRaw('return_reason, COUNT(*) as so_dong, SUM(so_luong_hoan) as so_luong_hoan')
            ->groupBy('return_reason')
            ->orderByDesc('so_luong_hoan')
            ->limit(10)
            ->get();

        $onlineRows = (clone $base)
            ->selectRaw($onlineProductName.' as ten_san_pham, '.$onlineColorName.' as mau, ct.size, SUM(ct.so_luong) as so_luong, SUM(ct.thanh_tien) as tong_tien')
            ->groupByRaw($onlineProductName.', '.$onlineColorName.', ct.size')
            ->orderByDesc('tong_tien')
            ->paginate($onlineFilters['per_page'], ['*'], 'online_page')
            ->withQueryString();

        return [
            'onlineFilters' => $onlineFilters,
            'onlineTotals' => $onlineTotals,
            'onlineReturnTotals' => $onlineReturnTotals,
            'onlineRevenueChange' => $onlineRevenueChange,
            'onlineTrend' => $onlineTrend,
            'onlineDailyRows' => $onlineDailyRows,
            'onlineTopProducts' => $onlineTopProducts,
            'onlineTopReturnProducts' => $onlineTopReturnProducts,
            'onlineTopReturnReasons' => $onlineTopReturnReasons,
            'onlineRows' => $onlineRows,
            'onlineProducts' => DB::table('don_hang_hoan_thanh as dhht')
                ->leftJoin('online_product_aliases as opa', 'opa.original_name', '=', 'dhht.ten_san_pham')
                ->whereNull('dhht.deleted_at')
                ->whereNotNull('dhht.ten_san_pham')
                ->where('dhht.ten_san_pham', '<>', '')
                ->selectRaw($onlineProductName.' as ten_san_pham')
                ->union(
                    DB::table('hang_hoan_online_chi_tiet as hhct')
                        ->leftJoin('online_product_aliases as opa', 'opa.original_name', '=', 'hhct.ten_san_pham')
                        ->whereNull('hhct.deleted_at')
                        ->whereNotNull('hhct.ten_san_pham')
                        ->where('hhct.ten_san_pham', '<>', '')
                        ->selectRaw('COALESCE(opa.group_name, hhct.ten_san_pham) as ten_san_pham')
                )
                ->orderBy('ten_san_pham')
                ->pluck('ten_san_pham')
                ->unique()
                ->values(),
            'onlineMaus' => DB::table('don_hang_hoan_thanh_chi_tiet as ct')
                ->join('don_hang_hoan_thanh as dhht', 'dhht.id', '=', 'ct.don_hang_hoan_thanh_id')
                ->leftJoin('online_color_aliases as oca', 'oca.original_name', '=', 'ct.mau')
                ->whereNull('ct.deleted_at')
                ->whereNull('dhht.deleted_at')
                ->whereNotNull('ct.mau')
                ->where('ct.mau', '<>', '')
                ->selectRaw($onlineColorName.' as mau')
                ->union(
                    DB::table('hang_hoan_online_chi_tiet as hhct')
                        ->leftJoin('online_color_aliases as oca', 'oca.original_name', '=', 'hhct.mau')
                        ->whereNull('hhct.deleted_at')
                        ->whereNotNull('hhct.mau')
                        ->where('hhct.mau', '<>', '')
                        ->selectRaw('COALESCE(oca.group_name, hhct.mau) as mau')
                )
                ->orderBy('mau')
                ->pluck('mau')
                ->unique()
                ->values(),
            'onlineSizes' => DB::table('don_hang_hoan_thanh_chi_tiet as ct')
                ->join('don_hang_hoan_thanh as dhht', 'dhht.id', '=', 'ct.don_hang_hoan_thanh_id')
                ->whereNull('ct.deleted_at')
                ->whereNull('dhht.deleted_at')
                ->whereNotNull('ct.size')
                ->where('ct.size', '<>', '')
                ->select('ct.size')
                ->union(
                    DB::table('hang_hoan_online_chi_tiet as hhct')
                        ->whereNull('hhct.deleted_at')
                        ->whereNotNull('hhct.size')
                        ->where('hhct.size', '<>', '')
                        ->select('hhct.size')
                )
                ->orderBy('size')
                ->pluck('size')
                ->unique()
                ->values(),
        ];
    }

    private function onlineSalesBaseQuery(array $filters): Builder
    {
        $onlineProductName = $this->onlineProductNameExpression();
        $onlineColorName = $this->onlineColorNameExpression();

        return DB::table('don_hang_hoan_thanh_chi_tiet as ct')
            ->join('don_hang_hoan_thanh as dhht', 'dhht.id', '=', 'ct.don_hang_hoan_thanh_id')
            ->leftJoin('online_product_aliases as opa', 'opa.original_name', '=', 'dhht.ten_san_pham')
            ->leftJoin('online_color_aliases as oca', 'oca.original_name', '=', 'ct.mau')
            ->whereNull('ct.deleted_at')
            ->whereNull('dhht.deleted_at')
            ->whereDate('dhht.ngay_hoan_thanh', '>=', $filters['tu_ngay'])
            ->whereDate('dhht.ngay_hoan_thanh', '<=', $filters['den_ngay'])
            ->when($filters['ma_hang'] !== '', fn (Builder $query) => $query->whereRaw($onlineProductName.' = ?', [$filters['ma_hang']]))
            ->when($filters['mau'] !== '', fn (Builder $query) => $query->whereRaw($onlineColorName.' = ?', [$filters['mau']]))
            ->when($filters['size'] !== '', fn (Builder $query) => $query->where('ct.size', $filters['size']))
            ->when($filters['kenh_ban'] !== '', fn (Builder $query) => $query->where('dhht.kenh_ban', $filters['kenh_ban']));
    }

    private function onlineReturnsBaseQuery(array $filters): Builder
    {
        $returnDate = 'DATE(COALESCE(hhct.refund_time, hho.ngay_hoan))';

        return DB::table('hang_hoan_online_chi_tiet as hhct')
            ->join('hang_hoan_online as hho', 'hho.id', '=', 'hhct.hang_hoan_online_id')
            ->leftJoin('online_product_aliases as opa', 'opa.original_name', '=', 'hhct.ten_san_pham')
            ->leftJoin('online_color_aliases as oca', 'oca.original_name', '=', 'hhct.mau')
            ->whereNull('hhct.deleted_at')
            ->whereNull('hho.deleted_at')
            ->where('hhct.cong_ton', true)
            ->whereDate(DB::raw($returnDate), '>=', $filters['tu_ngay'])
            ->whereDate(DB::raw($returnDate), '<=', $filters['den_ngay'])
            ->when($filters['ma_hang'] !== '', fn (Builder $query) => $query->whereRaw('COALESCE(opa.group_name, hhct.ten_san_pham) = ?', [$filters['ma_hang']]))
            ->when($filters['mau'] !== '', fn (Builder $query) => $query->whereRaw('COALESCE(oca.group_name, hhct.mau) = ?', [$filters['mau']]))
            ->when($filters['size'] !== '', fn (Builder $query) => $query->where('hhct.size', $filters['size']));
    }

    private function onlineProductNameExpression(): string
    {
        return 'COALESCE(opa.group_name, dhht.ten_san_pham)';
    }

    private function onlineColorNameExpression(): string
    {
        return 'COALESCE(oca.group_name, ct.mau)';
    }

    private function onlineSoldQuantity(array $filters): float
    {
        return (float) $this->onlineSalesBaseQuery($filters)->sum('ct.so_luong');
    }

    private function orderSummaryReport(Request $request, BaoCaoTongHopDonHangService $service): array
    {
        $orderFilters = [
            'q' => trim((string) $request->input('order_q')),
            'ma_don' => trim((string) $request->input('order_ma_don')),
            'ma_kh' => trim((string) $request->input('order_ma_kh')),
            'mat_hang_id' => $request->integer('order_mat_hang_id') ?: null,
            'mau_id' => $request->integer('order_mau_id') ?: null,
            'size_id' => $request->integer('order_size_id') ?: null,
            'ngay_nhan_tu' => trim((string) $request->input('order_ngay_nhan_tu')),
            'ngay_nhan_den' => trim((string) $request->input('order_ngay_nhan_den')),
            'han_giao_tu' => trim((string) $request->input('order_han_giao_tu')),
            'han_giao_den' => trim((string) $request->input('order_han_giao_den')),
            'per_page' => in_array($request->integer('order_per_page'), paginationPerPageOptions(), true)
                ? $request->integer('order_per_page')
                : paginationPerPage(),
        ];

        $orderRows = $service->query()
            ->when($orderFilters['q'] !== '', function (Builder $query) use ($orderFilters) {
                $keyword = $orderFilters['q'];
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('ma_don', 'like', "%{$keyword}%")
                        ->orWhere('ma_kh', 'like', "%{$keyword}%")
                        ->orWhere('ma_hang', 'like', "%{$keyword}%")
                        ->orWhere('ten_hang', 'like', "%{$keyword}%")
                        ->orWhere('ten_mau', 'like', "%{$keyword}%")
                        ->orWhere('ten_size', 'like', "%{$keyword}%");
                });
            })
            ->when($orderFilters['ma_don'] !== '', fn (Builder $query) => $query->where('ma_don', 'like', "%{$orderFilters['ma_don']}%"))
            ->when($orderFilters['ma_kh'] !== '', fn (Builder $query) => $query->where('ma_kh', 'like', "%{$orderFilters['ma_kh']}%"))
            ->when($orderFilters['mat_hang_id'], fn (Builder $query) => $query->where('dct.mat_hang_id', $orderFilters['mat_hang_id']))
            ->when($orderFilters['mau_id'], fn (Builder $query) => $query->where('dct.mau_id', $orderFilters['mau_id']))
            ->when($orderFilters['size_id'], fn (Builder $query) => $query->where('dct.size_id', $orderFilters['size_id']))
            ->when($orderFilters['ngay_nhan_tu'] !== '', fn (Builder $query) => $query->whereDate('ngay_nhan', '>=', $orderFilters['ngay_nhan_tu']))
            ->when($orderFilters['ngay_nhan_den'] !== '', fn (Builder $query) => $query->whereDate('ngay_nhan', '<=', $orderFilters['ngay_nhan_den']))
            ->when($orderFilters['han_giao_tu'] !== '', fn (Builder $query) => $query->whereDate('han_giao', '>=', $orderFilters['han_giao_tu']))
            ->when($orderFilters['han_giao_den'] !== '', fn (Builder $query) => $query->whereDate('han_giao', '<=', $orderFilters['han_giao_den']))
            ->orderByDesc('ngay_nhan')
            ->orderBy('ma_don')
            ->orderBy('don_hang_chi_tiet_id')
            ->paginate($orderFilters['per_page'], ['*'], 'order_page')
            ->withQueryString();

        $donHangChiTietTable = (new DonHangChiTiet)->getTable();

        return [
            'orderFilters' => $orderFilters,
            'orderRows' => $orderRows,
            'orderMatHangs' => DB::table($donHangChiTietTable.' as dct')
                ->join('dm_mat_hang as mh', 'mh.id', '=', 'dct.mat_hang_id')
                ->select('mh.id', 'mh.ma_hang', 'mh.ten_hang')
                ->whereNull('dct.deleted_at')
                ->whereNull('mh.deleted_at')
                ->distinct()
                ->orderBy('mh.ten_hang')
                ->get(),
            'orderMaus' => DB::table($donHangChiTietTable.' as dct')
                ->join('dm_mau as mau', 'mau.id', '=', 'dct.mau_id')
                ->select('mau.id', 'mau.ten_mau')
                ->whereNull('dct.deleted_at')
                ->whereNull('mau.deleted_at')
                ->distinct()
                ->orderBy('mau.ten_mau')
                ->get(),
            'orderSizes' => DB::table($donHangChiTietTable.' as dct')
                ->join('dm_size as sz', 'sz.id', '=', 'dct.size_id')
                ->select('sz.id', 'sz.ten_size')
                ->whereNull('dct.deleted_at')
                ->whereNull('sz.deleted_at')
                ->distinct()
                ->orderBy('sz.ten_size')
                ->get(),
        ];
    }

    private function dateInput(Request $request, string $key, string $default): string
    {
        $value = trim((string) $request->input($key));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $default;
    }

    private function onlineSalesDateRange(): array
    {
        $bounds = DB::table('don_hang_hoan_thanh')
            ->whereNull('deleted_at')
            ->selectRaw('MIN(ngay_hoan_thanh) as tu_ngay, MAX(ngay_hoan_thanh) as den_ngay')
            ->first();

        return [
            'tu_ngay' => $bounds?->tu_ngay
                ? Carbon::parse($bounds->tu_ngay)->toDateString()
                : now()->subDays(89)->toDateString(),
            'den_ngay' => $bounds?->den_ngay
                ? Carbon::parse($bounds->den_ngay)->toDateString()
                : now()->toDateString(),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'matHangs' => DB::table('dm_mat_hang')
                ->whereNull('deleted_at')
                ->where('trang_thai', true)
                ->select('id', 'ma_hang', 'ten_hang')
                ->orderBy('ma_hang')
                ->get(),
            'maus' => DB::table('dm_mau')
                ->whereNull('deleted_at')
                ->where('trang_thai', true)
                ->select('id', 'ten_mau')
                ->orderBy('ten_mau')
                ->get(),
            'sizes' => DB::table('dm_size')
                ->whereNull('deleted_at')
                ->where('trang_thai', true)
                ->select('id', 'ten_size')
                ->orderBy('ten_size')
                ->get(),
            'kenhBans' => DmKenhBan::activeNames(),
        ];
    }
}
