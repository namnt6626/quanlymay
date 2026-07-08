<?php

namespace App\Services\DonHangHoanThanh;

use App\Models\DonHangHoanThanh;
use Illuminate\Support\Facades\DB;

class DonHangHoanThanhService
{
    public function saveManual(array $data, ?DonHangHoanThanh $current = null): DonHangHoanThanh
    {
        return DB::transaction(function () use ($data, $current) {
            $data['ten_kho'] = null;
            $order = $current ?: $this->findOrCreate($data['ngay_hoan_thanh'], $data['ten_san_pham'], $data['kenh_ban']);
            $order->update(collect($data)->only(['ngay_hoan_thanh', 'ten_san_pham', 'ten_kho', 'kenh_ban', 'ghi_chu'])->all());
            if ($current) $order->chiTiets()->delete();
            foreach ($data['chi_tiets'] as $detail) $this->mergeDetail($order, [...$detail, 'nguon' => $detail['nguon'] ?? 'thu_cong']);
            return $order;
        });
    }

    public function import(array $rows): int
    {
        return DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $order = $this->findOrCreate($row['ngay_hoan_thanh'], $row['ten_san_pham'], $row['kenh_ban']);
                $this->mergeDetail($order, [
                    'mau' => $row['mau'] ?? null, 'size' => $row['size'] ?? null,
                    'phan_loai_goc' => $row['phan_loai_goc'] ?? null,
                    'so_luong' => $row['so_luong'], 'thanh_tien' => $row['thanh_tien'],
                    'nguon' => 'excel', 'thoi_gian_tao_goc' => $row['thoi_gian_tao_goc'] ?? null,
                ]);
            }
            return count($rows);
        });
    }

    private function findOrCreate(string $date, string $product, string $channel): DonHangHoanThanh
    {
        return DonHangHoanThanh::query()->firstOrCreate([
            'ngay_hoan_thanh' => $date,
            'ten_san_pham' => trim($product),
            'ten_kho' => null,
            'kenh_ban' => trim($channel),
        ]);
    }

    private function mergeDetail(DonHangHoanThanh $order, array $data): void
    {
        $query = $order->chiTiets()->where('nguon', $data['nguon']);
        foreach (['mau', 'size'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            $value === '' ? $query->whereNull($field) : $query->where($field, $value);
            $data[$field] = $value !== '' ? $value : null;
        }
        $existing = $query->first();
        if ($existing) {
            $existing->update([
                'so_luong' => (float) $existing->so_luong + (float) $data['so_luong'],
                'thanh_tien' => (float) $existing->thanh_tien + (float) $data['thanh_tien'],
                'phan_loai_goc' => $existing->phan_loai_goc ?: ($data['phan_loai_goc'] ?? null),
            ]);
            return;
        }
        $order->chiTiets()->create($data);
    }
}
