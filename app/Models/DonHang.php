<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonHang extends Model
{
  use SoftDeletes;

  protected $table = 'don_hangs';

  protected $fillable = [
    'ngay_nhan',
    'ma_don',
    'ma_kh',
    'han_giao',
    'kenh_ban',
    'ghi_chu',
  ];

  protected function casts(): array
  {
    return [
      'ngay_nhan' => 'date',
      'han_giao' => 'date',
    ];
  }

  public function chiTiets(): HasMany
  {
    return $this->hasMany(DonHangChiTiet::class, 'don_hang_id');
  }
}
