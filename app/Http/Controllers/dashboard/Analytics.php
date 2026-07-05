<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
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
        $onlineFilters = [
            'tu_ngay' => $this->dateInput($request, 'online_tu_ngay', now()->subDays(89)->toDateString()),
            'den_ngay' => $this->dateInput($request, 'online_den_ngay', now()->toDateString()),
            'q' => trim((string) $request->input('online_q')),
            'ten_kho' => trim((string) $request->input('online_ten_kho')),
            'per_page' => in_array($request->integer('online_per_page'), paginationPerPageOptions(), true)
                ? $request->integer('online_per_page')
                : paginationPerPage(),
        ];

        if (Carbon::parse($onlineFilters['tu_ngay'])->gt(Carbon::parse($onlineFilters['den_ngay']))) {
            $onlineFilters['tu_ngay'] = now()->subDays(89)->toDateString();
            $onlineFilters['den_ngay'] = now()->toDateString();
        }

        $base = $this->onlineSalesBaseQuery($onlineFilters);
        $onlineTotals = (clone $base)->selectRaw('
            COALESCE(SUM(ct.so_luong), 0) as tong_so_luong,
            COALESCE(SUM(ct.thanh_tien), 0) as tong_tien_ban_hang,
            COUNT(DISTINCT dhht.ngay_hoan_thanh) as so_ngay_ban
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

        $onlineTopProducts = (clone $base)
            ->selectRaw('dhht.ten_san_pham, SUM(ct.so_luong) as so_luong, SUM(ct.thanh_tien) as tong_tien')
            ->groupBy('dhht.ten_san_pham')
            ->orderByDesc('so_luong')
            ->limit(10)
            ->get();

        $onlineRows = (clone $base)
            ->selectRaw('dhht.ten_san_pham, dhht.ten_kho, ct.mau, ct.size, SUM(ct.so_luong) as so_luong, SUM(ct.thanh_tien) as tong_tien')
            ->groupBy('dhht.ten_san_pham', 'dhht.ten_kho', 'ct.mau', 'ct.size')
            ->orderByDesc('tong_tien')
            ->paginate($onlineFilters['per_page'], ['*'], 'online_page')
            ->withQueryString();

        return [
            'onlineFilters' => $onlineFilters,
            'onlineTotals' => $onlineTotals,
            'onlineRevenueChange' => $onlineRevenueChange,
            'onlineTrend' => $onlineTrend,
            'onlineDailyRows' => $onlineDailyRows,
            'onlineTopProducts' => $onlineTopProducts,
            'onlineRows' => $onlineRows,
            'onlineWarehouses' => DB::table('don_hang_hoan_thanh')
                ->whereNull('deleted_at')
                ->whereNotNull('ten_kho')
                ->where('ten_kho', '<>', '')
                ->distinct()
                ->orderBy('ten_kho')
                ->pluck('ten_kho'),
        ];
    }

    private function onlineSalesBaseQuery(array $filters): Builder
    {
        return DB::table('don_hang_hoan_thanh_chi_tiet as ct')
            ->join('don_hang_hoan_thanh as dhht', 'dhht.id', '=', 'ct.don_hang_hoan_thanh_id')
            ->whereNull('ct.deleted_at')
            ->whereNull('dhht.deleted_at')
            ->whereDate('dhht.ngay_hoan_thanh', '>=', $filters['tu_ngay'])
            ->whereDate('dhht.ngay_hoan_thanh', '<=', $filters['den_ngay'])
            ->when($filters['q'] !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($filters) {
                $keyword = $filters['q'];
                $query->where('dhht.ten_san_pham', 'like', "%{$keyword}%")
                    ->orWhere('ct.mau', 'like', "%{$keyword}%")
                    ->orWhere('ct.size', 'like', "%{$keyword}%");
            }))
            ->when($filters['ten_kho'] !== '', fn (Builder $query) => $query->where('dhht.ten_kho', $filters['ten_kho']));
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

    private function filterOptions(): array
    {
        $donHangTable = (new DonHang)->getTable();

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
            'kenhBans' => DB::table($donHangTable)
                ->whereNull('deleted_at')
                ->whereNotNull('kenh_ban')
                ->where('kenh_ban', '<>', '')
                ->distinct()
                ->orderBy('kenh_ban')
                ->pluck('kenh_ban'),
        ];
    }
}
