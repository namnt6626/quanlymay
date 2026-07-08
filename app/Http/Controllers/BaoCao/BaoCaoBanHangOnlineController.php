<?php

namespace App\Http\Controllers\BaoCao;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BaoCaoBanHangOnlineController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate([
            'tu_ngay' => ['nullable', 'date'],
            'den_ngay' => ['nullable', 'date', 'after_or_equal:tu_ngay'],
            'q' => ['nullable', 'string', 'max:500'],
        ]);

        $filters = [
            'tu_ngay' => $request->input('tu_ngay') ?: now()->subDays(89)->toDateString(),
            'den_ngay' => $request->input('den_ngay') ?: now()->toDateString(),
            'q' => trim((string) $request->input('q')),
            'per_page' => paginationPerPage(),
        ];

        $base = $this->baseQuery($filters);
        $totals = (clone $base)->selectRaw('
            COALESCE(SUM(ct.so_luong), 0) as tong_so_luong,
            COALESCE(SUM(ct.thanh_tien), 0) as tong_doanh_thu,
            COUNT(DISTINCT dhht.ngay_hoan_thanh) as so_ngay_ban
        ')->first();

        $from = Carbon::parse($filters['tu_ngay']);
        $to = Carbon::parse($filters['den_ngay']);
        $days = (int) $from->diffInDays($to) + 1;
        $previousFilters = [...$filters,
            'tu_ngay' => $from->copy()->subDays($days)->toDateString(),
            'den_ngay' => $from->copy()->subDay()->toDateString(),
        ];
        $previousRevenue = (float) $this->baseQuery($previousFilters)->sum('ct.thanh_tien');
        $currentRevenue = (float) ($totals->tong_doanh_thu ?? 0);
        $revenueChange = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : null;

        $trend = (clone $base)
            ->selectRaw('dhht.ngay_hoan_thanh as ngay, SUM(ct.so_luong) as so_luong, SUM(ct.thanh_tien) as doanh_thu')
            ->groupBy('dhht.ngay_hoan_thanh')->orderBy('dhht.ngay_hoan_thanh')->get();

        $topProducts = (clone $base)
            ->selectRaw('dhht.ten_san_pham, SUM(ct.so_luong) as so_luong, SUM(ct.thanh_tien) as doanh_thu')
            ->groupBy('dhht.ten_san_pham')->orderByDesc('so_luong')->limit(10)->get();

        $rows = (clone $base)
            ->selectRaw('dhht.ten_san_pham, ct.mau, ct.size, SUM(ct.so_luong) as so_luong, SUM(ct.thanh_tien) as doanh_thu')
            ->groupBy('dhht.ten_san_pham', 'ct.mau', 'ct.size')
            ->orderByDesc('doanh_thu')->paginate($filters['per_page'])->withQueryString();

        return view('content.bao-cao.ban-hang-online.index', [
            'filters' => $filters, 'totals' => $totals, 'revenueChange' => $revenueChange,
            'trend' => $trend, 'topProducts' => $topProducts, 'rows' => $rows,
        ]);
    }

    private function baseQuery(array $filters): Builder
    {
        return DB::table('don_hang_hoan_thanh_chi_tiet as ct')
            ->join('don_hang_hoan_thanh as dhht', 'dhht.id', '=', 'ct.don_hang_hoan_thanh_id')
            ->whereNull('ct.deleted_at')->whereNull('dhht.deleted_at')
            ->whereDate('dhht.ngay_hoan_thanh', '>=', $filters['tu_ngay'])
            ->whereDate('dhht.ngay_hoan_thanh', '<=', $filters['den_ngay'])
            ->when($filters['q'] !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($filters) {
                $keyword = $filters['q'];
                $query->where('dhht.ten_san_pham', 'like', "%{$keyword}%")
                    ->orWhere('ct.mau', 'like', "%{$keyword}%")->orWhere('ct.size', 'like', "%{$keyword}%");
            }));
    }
}
