<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonHangHoanThanhChiTiet extends Model
{
    use SoftDeletes;

    protected $table = 'don_hang_hoan_thanh_chi_tiet';

    protected $fillable = [
        'don_hang_hoan_thanh_id', 'mau', 'size', 'phan_loai_goc', 'so_luong',
        'thanh_tien', 'nguon', 'thoi_gian_tao_goc',
    ];

    protected function casts(): array
    {
        return [
            'so_luong' => 'decimal:4',
            'thanh_tien' => 'decimal:2',
            'thoi_gian_tao_goc' => 'datetime',
        ];
    }

    public function donHangHoanThanh(): BelongsTo
    {
        return $this->belongsTo(DonHangHoanThanh::class, 'don_hang_hoan_thanh_id');
    }
}
