<?php

namespace App\Http\Controllers\DonHangOnline;

use App\Http\Controllers\Controller;
use App\Models\DonHangHoanThanhChiTiet;
use App\Models\NhapHangOnlineChiTiet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TonKhoOnlineController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'ten_san_pham' => trim((string) $request->input('ten_san_pham')),
            'mau' => trim((string) $request->input('mau')),
            'size' => trim((string) $request->input('size')),
        ];

        $imports = NhapHangOnlineChiTiet::query()
            ->selectRaw("ten_san_pham, COALESCE(mau, '') as mau_key, COALESCE(size, '') as size_key, SUM(so_luong) as so_luong_nhap, SUM(thanh_tien) as tien_nhap")
            ->groupBy('ten_san_pham', DB::raw("COALESCE(mau, '')"), DB::raw("COALESCE(size, '')"))
            ->get();

        $exports = DonHangHoanThanhChiTiet::query()
            ->join('don_hang_hoan_thanh', 'don_hang_hoan_thanh.id', '=', 'don_hang_hoan_thanh_chi_tiet.don_hang_hoan_thanh_id')
            ->whereNull('don_hang_hoan_thanh.deleted_at')
            ->selectRaw("don_hang_hoan_thanh.ten_san_pham, COALESCE(don_hang_hoan_thanh_chi_tiet.mau, '') as mau_key, COALESCE(don_hang_hoan_thanh_chi_tiet.size, '') as size_key, SUM(don_hang_hoan_thanh_chi_tiet.so_luong) as so_luong_xuat, SUM(don_hang_hoan_thanh_chi_tiet.thanh_tien) as tien_xuat")
            ->groupBy('don_hang_hoan_thanh.ten_san_pham', DB::raw("COALESCE(don_hang_hoan_thanh_chi_tiet.mau, '')"), DB::raw("COALESCE(don_hang_hoan_thanh_chi_tiet.size, '')"))
            ->get();

        $rows = $this->mergeRows($this->normalizeStockRows($imports, 'import'), $this->normalizeStockRows($exports, 'export'))
            ->filter(function (array $row) use ($filters): bool {
                if ($filters['ten_san_pham'] !== '' && ! str_contains($this->normalizeText($row['ten_san_pham']), $this->normalizeText($filters['ten_san_pham']))) {
                    return false;
                }
                if ($filters['mau'] !== '' && $this->normalizeText((string) $row['mau']) !== $this->normalizeText($filters['mau'])) {
                    return false;
                }
                if ($filters['size'] !== '' && $this->normalizeSize((string) $row['size']) !== $this->normalizeSize($filters['size'])) {
                    return false;
                }
                return true;
            })
            ->sortBy([['ten_san_pham', 'asc'], ['mau', 'asc'], ['size', 'asc']])
            ->values();

        $totals = [
            'so_luong_nhap' => $rows->sum('so_luong_nhap'),
            'so_luong_xuat' => $rows->sum('so_luong_xuat'),
            'so_luong_ton' => $rows->sum('so_luong_ton'),
            'tien_nhap' => $rows->sum('tien_nhap'),
            'tien_xuat' => $rows->sum('tien_xuat'),
            'chenh_lech_tien' => $rows->sum('chenh_lech_tien'),
        ];

        $filterOptions = $this->filterOptions();

        return view('content.don-hang-online.ton-kho.index', compact('rows', 'filters', 'totals', 'filterOptions'));
    }

    private function mergeRows(Collection $imports, Collection $exports): Collection
    {
        return $imports->keys()->merge($exports->keys())->unique()->map(function (string $key) use ($imports, $exports): array {
            $import = $imports->get($key);
            $export = $exports->get($key);
            $quantityIn = (float) ($import->so_luong_nhap ?? 0);
            $quantityOut = (float) ($export->so_luong_xuat ?? 0);
            $moneyIn = (float) ($import->tien_nhap ?? 0);
            $moneyOut = (float) ($export->tien_xuat ?? 0);

            return [
                'ten_san_pham' => $import->ten_san_pham ?? $export->ten_san_pham,
                'mau' => ($import->mau_key ?? $export->mau_key) ?: null,
                'size' => ($import->size_key ?? $export->size_key) ?: null,
                'so_luong_nhap' => $quantityIn,
                'so_luong_xuat' => $quantityOut,
                'so_luong_ton' => $quantityIn - $quantityOut,
                'tien_nhap' => $moneyIn,
                'tien_xuat' => $moneyOut,
                'chenh_lech_tien' => $moneyOut - $moneyIn,
            ];
        });
    }

    private function normalizeStockRows(Collection $rows, string $type): Collection
    {
        return $rows->reduce(function (Collection $carry, object $row) use ($type): Collection {
            $key = $this->key($row->ten_san_pham, $row->mau_key, $row->size_key);
            $current = $carry->get($key);

            if (! $current) {
                $current = (object) [
                    'ten_san_pham' => $row->ten_san_pham,
                    'mau_key' => $row->mau_key,
                    'size_key' => $row->size_key,
                    'so_luong_nhap' => 0,
                    'tien_nhap' => 0,
                    'so_luong_xuat' => 0,
                    'tien_xuat' => 0,
                ];
            }

            if ($type === 'import') {
                $current->so_luong_nhap += (float) $row->so_luong_nhap;
                $current->tien_nhap += (float) $row->tien_nhap;
            } else {
                $current->so_luong_xuat += (float) $row->so_luong_xuat;
                $current->tien_xuat += (float) $row->tien_xuat;
            }

            $carry->put($key, $current);

            return $carry;
        }, collect());
    }

    private function key(string $product, ?string $color, ?string $size): string
    {
        return $this->normalizeText($product).'|'.$this->normalizeText((string) $color).'|'.$this->normalizeSize((string) $size);
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ]);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '');
    }

    private function normalizeSize(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', $this->normalizeText($value)) ?? '';
    }

    private function filterOptions(): array
    {
        $importProducts = NhapHangOnlineChiTiet::query()->whereNotNull('ten_san_pham')->pluck('ten_san_pham');
        $exportProducts = DonHangHoanThanhChiTiet::query()
            ->join('don_hang_hoan_thanh', 'don_hang_hoan_thanh.id', '=', 'don_hang_hoan_thanh_chi_tiet.don_hang_hoan_thanh_id')
            ->whereNull('don_hang_hoan_thanh.deleted_at')
            ->pluck('don_hang_hoan_thanh.ten_san_pham');

        $importColors = NhapHangOnlineChiTiet::query()->whereNotNull('mau')->where('mau', '<>', '')->pluck('mau');
        $exportColors = DonHangHoanThanhChiTiet::query()->whereNotNull('mau')->where('mau', '<>', '')->pluck('mau');

        $importSizes = NhapHangOnlineChiTiet::query()->whereNotNull('size')->where('size', '<>', '')->pluck('size');
        $exportSizes = DonHangHoanThanhChiTiet::query()->whereNotNull('size')->where('size', '<>', '')->pluck('size');

        return [
            'products' => $importProducts->merge($exportProducts)->unique()->sort()->values(),
            'colors' => $importColors->merge($exportColors)->unique()->sort()->values(),
            'sizes' => $importSizes->merge($exportSizes)->unique()->sort()->values(),
        ];
    }
}
