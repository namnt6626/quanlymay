<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonHangHoanThanh extends Model
{
    use SoftDeletes;

    protected $table = 'don_hang_hoan_thanh';

    protected $fillable = ['ngay_hoan_thanh', 'ten_san_pham', 'ten_kho', 'kenh_ban', 'ghi_chu'];

    protected function casts(): array
    {
        return ['ngay_hoan_thanh' => 'date'];
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(DonHangHoanThanhChiTiet::class, 'don_hang_hoan_thanh_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $model): void {
            $model->chiTiets()->get()->each->delete();
        });
    }
}
