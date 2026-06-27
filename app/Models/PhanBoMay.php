<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhanBoMay extends Model
{
  use SoftDeletes;

  protected $table = 'phan_bo_may';

  protected $fillable = [
    'cat_id',
    'don_hang_chi_tiet_id',
    'ngay_phan_bo',
    'don_vi_may_id',
    'so_luong_giao',
    'ghi_chu',
  ];

  protected function casts(): array
  {
    return [
      'ngay_phan_bo' => 'date',
      'so_luong_giao' => 'decimal:4',
    ];
  }

  public function cat(): BelongsTo
  {
    return $this->belongsTo(Cat::class, 'cat_id');
  }

  public function donHangChiTiet(): BelongsTo
  {
    return $this->belongsTo(DonHangChiTiet::class, 'don_hang_chi_tiet_id');
  }

  public function donViMay(): BelongsTo
  {
    return $this->belongsTo(DmDonViMay::class, 'don_vi_may_id');
  }

  public function qcs(): HasMany
  {
    return $this->hasMany(Qc::class, 'phan_bo_may_id');
  }

  protected static function booted(): void
  {
    static::deleting(function (PhanBoMay $phanBoMay): void {
      $qcs = $phanBoMay->isForceDeleting()
        ? $phanBoMay->qcs()->withTrashed()->get()
        : $phanBoMay->qcs()->get();

      $qcs->each(function (Qc $qc) use ($phanBoMay): void {
        $phanBoMay->isForceDeleting() ? $qc->forceDelete() : $qc->delete();
      });
    });
  }
}
