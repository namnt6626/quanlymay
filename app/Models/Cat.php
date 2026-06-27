<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cat extends Model
{
    use SoftDeletes;

    protected $table = 'cat';

    protected $fillable = [
        'ngay_cat',
        'don_hang_chi_tiet_id',
        'mat_hang_id',
        'mau_id',
        'size_id',
        'ban_cat_id',
        'don_vi_cat_id',
        'so_luong_cat',
        'dinh_muc',
        'vai_tieu_hao',
        'ghi_chu',
    ];

    protected function casts(): array
    {
        return [
            'ngay_cat' => 'date',
            'so_luong_cat' => 'decimal:4',
            'dinh_muc' => 'decimal:4',
            'vai_tieu_hao' => 'decimal:4',
        ];
    }

    public function matHang(): BelongsTo
    {
        return $this->belongsTo(MatHang::class, 'mat_hang_id');
    }

    public function mau(): BelongsTo
    {
        return $this->belongsTo(Mau::class, 'mau_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(DmSize::class, 'size_id');
    }

    public function donHangChiTiet(): BelongsTo
    {
        return $this->belongsTo(DonHangChiTiet::class, 'don_hang_chi_tiet_id');
    }

    public function banCat(): BelongsTo
    {
        return $this->belongsTo(DmBanCat::class, 'ban_cat_id');
    }

    public function donViCat(): BelongsTo
    {
        return $this->belongsTo(DmDonViCat::class, 'don_vi_cat_id');
    }

    public function phanBoMays(): HasMany
    {
        return $this->hasMany(PhanBoMay::class, 'cat_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Cat $cat): void {
            $phanBoMays = $cat->isForceDeleting()
                ? $cat->phanBoMays()->withTrashed()->get()
                : $cat->phanBoMays()->get();

            $phanBoMays->each(function (PhanBoMay $phanBoMay) use ($cat): void {
                $cat->isForceDeleting() ? $phanBoMay->forceDelete() : $phanBoMay->delete();
            });
        });
    }
}
