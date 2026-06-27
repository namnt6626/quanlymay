<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Services\Dashboard\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Analytics extends Controller
{
    public function index(Request $request, DashboardService $service): View
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

        return view('content.dashboard.dashboards-analytics', [
            'quickFilters' => $quickFilters,
            'timeFilters' => $timeFilters,
            'dailyFilters' => $dailyFilters,
            'maxWeekOfMonth' => $maxWeekOfMonth,
            'quickSummary' => $service->getQuickSummary($quickFilters),
            'timeProductionSummary' => $service->getTimeProductionSummary($timeFilters),
            'todayProduction' => $service->getTodayProduction(),
            'dailyProduction' => $dailyProduction,
            'dailyPerPage' => $dailyPerPage,
            ...$this->filterOptions(),
        ]);
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
