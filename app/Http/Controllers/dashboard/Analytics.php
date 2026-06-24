<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
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

        $timeFilters = [
            'date_from' => trim((string) $request->input('time_date_from')),
            'date_to' => trim((string) $request->input('time_date_to')),
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

        return view('content.dashboard.dashboards-analytics', [
            'quickFilters' => $quickFilters,
            'timeFilters' => $timeFilters,
            'dailyFilters' => $dailyFilters,
            'quickSummary' => $service->getQuickSummary($quickFilters),
            'timeProductionSummary' => $service->getTimeProductionSummary($timeFilters),
            'todayProduction' => $service->getTodayProduction(),
            'dailyProduction' => $service->getDailyProduction($dailyFilters),
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
