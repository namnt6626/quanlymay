<?php

namespace App\Observers;

use App\Models\Cat;
use App\Models\DmBanCat;
use App\Models\DmDonViCat;
use App\Models\DmDonViMay;
use App\Models\DmSize;
use App\Models\DonHang;
use App\Models\MatHang;
use App\Models\Mau;
use App\Models\NhapKho;
use App\Models\Permission;
use App\Models\PhanBoMay;
use App\Models\PhieuXuatKho;
use App\Models\Qc;
use App\Models\Role;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    public function created(Model $model): void
    {
        if (! $this->shouldLog($model)) {
            return;
        }

        ActivityLogger::log([
            'action' => 'CREATE',
            'module' => $this->moduleName($model),
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'description' => 'Tạo mới '.$this->recordName($model),
            'new_values' => ActivityLogger::modelValues($model),
        ]);
    }

    public function updated(Model $model): void
    {
        if (! $this->shouldLog($model)) {
            return;
        }

        $changes = ActivityLogger::changedValues($model);

        if ($changes['old'] === [] && $changes['new'] === []) {
            return;
        }

        ActivityLogger::log([
            'action' => 'UPDATE',
            'module' => $this->moduleName($model),
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'description' => 'Cập nhật '.$this->recordName($model),
            'old_values' => $changes['old'],
            'new_values' => $changes['new'],
        ]);
    }

    public function deleted(Model $model): void
    {
        if (! $this->shouldLog($model)) {
            return;
        }

        ActivityLogger::log([
            'action' => 'DELETE',
            'module' => $this->moduleName($model),
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'description' => 'Xóa '.$this->recordName($model),
            'old_values' => ActivityLogger::modelValues($model),
        ]);
    }

    public function restored(Model $model): void
    {
        if (! $this->shouldLog($model)) {
            return;
        }

        ActivityLogger::log([
            'action' => 'RESTORE',
            'module' => $this->moduleName($model),
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'description' => 'Khôi phục '.$this->recordName($model),
            'new_values' => ActivityLogger::modelValues($model),
        ]);
    }

    private function shouldLog(Model $model): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        if ($model instanceof NhapKho && (bool) $model->auto_from_qc) {
            return false;
        }

        return request()->hasSession();
    }

    private function moduleName(Model $model): string
    {
        return match (true) {
            $model instanceof MatHang => 'Mã hàng',
            $model instanceof Mau => 'Màu sắc',
            $model instanceof DmSize => 'Size',
            $model instanceof DmBanCat => 'Bàn cắt',
            $model instanceof DmDonViCat => 'Đơn vị cắt',
            $model instanceof DmDonViMay => 'Đơn vị may',
            $model instanceof DonHang => 'Đơn hàng',
            $model instanceof Cat => 'Cắt',
            $model instanceof PhanBoMay => 'Phân bổ may',
            $model instanceof Qc => 'QC',
            $model instanceof NhapKho => 'Nhập kho',
            $model instanceof PhieuXuatKho => 'Xuất kho',
            $model instanceof User => 'Tài khoản',
            $model instanceof Role => 'Vai trò',
            $model instanceof Permission => 'Quyền',
            default => class_basename($model),
        };
    }

    private function recordName(Model $model): string
    {
        $code = $model->ma_hang
            ?? $model->ma_mau
            ?? $model->ma_size
            ?? $model->ma_don_vi
            ?? $model->ma_don
            ?? $model->so_phieu
            ?? $model->username
            ?? $model->ma_vai_tro
            ?? $model->ma_quyen
            ?? null;

        return $this->moduleName($model).($code ? ' '.$code : ' #'.$model->getKey());
    }
}
