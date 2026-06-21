<?php

namespace App\Console\Commands;

use App\Models\NhapKho;
use App\Models\Qc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncQcToKhoCommand extends Command
{
    protected $signature = 'kho:sync-qc {--only-missing : Chỉ tạo nhập kho cho QC chưa có nhập kho tự động}';

    protected $description = 'Đồng bộ dữ liệu QC cũ sang nhập kho tự động.';

    public function handle(): int
    {
        $this->ensureRequiredSchema();

        $stats = [
            'scanned' => 0,
            'created' => 0,
            'updated' => 0,
            'restored' => 0,
            'deleted_zero' => 0,
            'skipped_auto' => 0,
            'skipped_existing' => 0,
            'skipped_empty' => 0,
            'errors' => 0,
        ];

        Qc::query()
            ->with(['phanBoMay.cat'])
            ->orderBy('id')
            ->chunkById(100, function ($qcs) use (&$stats): void {
                foreach ($qcs as $qc) {
                    $stats['scanned']++;

                    try {
                        $stats['created'] += $this->syncQc($qc, $stats);
                    } catch (\Throwable $exception) {
                        $stats['errors']++;
                        $this->error("QC #{$qc->id}: {$exception->getMessage()}");
                    }
                }
            });

        $this->line("Đã quét QC: {$stats['scanned']}");
        $this->line("Đã tạo nhập kho: {$stats['created']}");
        $this->line("Đã cập nhật nhập kho: {$stats['updated']}");
        $this->line("Đã khôi phục nhập kho: {$stats['restored']}");
        $this->line("Đã xóa mềm dòng về 0: {$stats['deleted_zero']}");
        $this->line("Đã đúng sẵn: {$stats['skipped_auto']}");
        $this->line("Bỏ qua đã có nhập kho liên quan: {$stats['skipped_existing']}");
        $this->line("Bỏ qua không có số lượng: {$stats['skipped_empty']}");
        $this->line("Số lỗi: {$stats['errors']}");
        $this->info('Hoàn tất sync QC sang kho.');

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function ensureRequiredSchema(): void
    {
        foreach (['qc_id', 'ngay_nhap', 'so_luong_nhap', 'loai_ton', 'auto_from_qc'] as $column) {
            if (! Schema::hasColumn('nhap_kho', $column)) {
                throw new \RuntimeException("Bảng nhap_kho thiếu cột {$column}. Hãy chạy migration trước.");
            }
        }

        foreach (['ngay_qc', 'so_luong_dat', 'so_luong_loi', 'so_luong_hong'] as $column) {
            if (! Schema::hasColumn('qc', $column)) {
                throw new \RuntimeException("Bảng qc thiếu cột {$column}.");
            }
        }
    }

    private function syncQc(Qc $qc, array &$stats): int
    {
        $hasManualNhapKho = NhapKho::withTrashed()
            ->where('qc_id', $qc->id)
            ->where(function ($query): void {
                $query->whereNull('auto_from_qc')->orWhere('auto_from_qc', false);
            })
            ->exists();

        if ($hasManualNhapKho) {
            $stats['skipped_existing']++;

            return 0;
        }

        $hasAutoNhapKho = NhapKho::withTrashed()
            ->where('qc_id', $qc->id)
            ->where('auto_from_qc', true)
            ->exists();

        $quantities = [
            'dat' => (float) $qc->so_luong_dat,
            'loi' => (float) $qc->so_luong_loi,
            'hong' => (float) $qc->so_luong_hong,
        ];

        if (array_sum($quantities) <= 0 && ! $hasAutoNhapKho) {
            $stats['skipped_empty']++;

            return 0;
        }

        return DB::transaction(function () use ($qc, $quantities, &$stats): int {
            $created = 0;
            foreach ($quantities as $loaiTon => $quantity) {
                $nhapKho = NhapKho::withTrashed()
                    ->where('qc_id', $qc->id)
                    ->where('loai_ton', $loaiTon)
                    ->where('auto_from_qc', true)
                    ->first();

                if ($quantity <= 0) {
                    if ($nhapKho && ! $nhapKho->trashed()) {
                        $nhapKho->delete();
                        $stats['deleted_zero']++;
                    }

                    continue;
                }

                $data = [
                    'qc_id' => $qc->id,
                    'ngay_nhap' => $qc->ngay_qc,
                    'so_luong_nhap' => $quantity,
                    'loai_ton' => $loaiTon,
                    'auto_from_qc' => true,
                    'ghi_chu' => 'Tự động nhập kho từ dữ liệu QC cũ',
                ];

                if (Schema::hasColumn('nhap_kho', 'don_hang_chi_tiet_id')) {
                    $data['don_hang_chi_tiet_id'] = $qc->don_hang_chi_tiet_id;
                }

                if (Schema::hasColumn('nhap_kho', 'mat_hang_id')) {
                    $data['mat_hang_id'] = $qc->phanBoMay?->cat?->mat_hang_id ?? $qc->mat_hang_id;
                }

                if (Schema::hasColumn('nhap_kho', 'mau_id')) {
                    $data['mau_id'] = $qc->phanBoMay?->cat?->mau_id ?? $qc->mau_id;
                }

                if (Schema::hasColumn('nhap_kho', 'size_id')) {
                    $data['size_id'] = $qc->phanBoMay?->cat?->size_id ?? $qc->size_id;
                }

                if (Schema::hasColumn('nhap_kho', 'don_vi_may_id')) {
                    $data['don_vi_may_id'] = $qc->phanBoMay?->don_vi_may_id;
                }

                if ($nhapKho) {
                    if ($nhapKho->trashed()) {
                        $nhapKho->restore();
                        $stats['restored']++;
                    }

                    $dirtyData = collect($data)
                        ->filter(fn ($value, string $key): bool => (string) ($nhapKho->{$key} ?? '') !== (string) $value)
                        ->all();

                    if ($dirtyData !== []) {
                        $nhapKho->forceFill($data)->save();
                        $stats['updated']++;
                    } else {
                        $stats['skipped_auto']++;
                    }

                    continue;
                }

                (new NhapKho)->forceFill($data)->save();
                $created++;
            }

            return $created;
        });
    }
}
