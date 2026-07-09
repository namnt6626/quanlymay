<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NhapHangOnline extends Model
{
    use SoftDeletes;

    protected $table = 'nhap_hang_online';

    protected $fillable = ['ngay_nhap', 'ghi_chu'];

    protected function casts(): array
    {
        return ['ngay_nhap' => 'date'];
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(NhapHangOnlineChiTiet::class, 'nhap_hang_online_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $model): void {
            $model->chiTiets()->get()->each->delete();
        });
    }
}
