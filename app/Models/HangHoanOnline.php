<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HangHoanOnline extends Model
{
    use SoftDeletes;

    protected $table = 'hang_hoan_online';

    protected $fillable = [
        'ngay_hoan',
        'tu_ngay',
        'den_ngay',
        'source',
        'ten_file',
        'tong_dong',
        'tong_so_luong',
        'tong_so_luong_cong_ton',
        'ghi_chu',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ngay_hoan' => 'date',
            'tu_ngay' => 'date',
            'den_ngay' => 'date',
            'tong_so_luong' => 'decimal:4',
            'tong_so_luong_cong_ton' => 'decimal:4',
        ];
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(HangHoanOnlineChiTiet::class, 'hang_hoan_online_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $model): void {
            $model->chiTiets()->get()->each->delete();
        });
    }
}
