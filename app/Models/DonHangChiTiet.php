<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonHangChiTiet extends Model
{
  use SoftDeletes;

  protected $table = 'don_hang_chi_tiets';

  protected $fillable = [
    'don_hang_id',
    'mat_hang_id',
    'mau_id',
    'size_id',
    'so_luong_dat',
    'ghi_chu',
  ];

  protected function casts(): array
  {
    return [
      'so_luong_dat' => 'decimal:4',
    ];
  }

  public function donHang(): BelongsTo
  {
    return $this->belongsTo(DonHang::class, 'don_hang_id');
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
}
