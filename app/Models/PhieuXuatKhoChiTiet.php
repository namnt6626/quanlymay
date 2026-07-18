<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhieuXuatKhoChiTiet extends Model
{
    use SoftDeletes;

    protected $table = 'phieu_xuat_kho_chi_tiet';

    protected $fillable = [
        'phieu_xuat_kho_id',
        'nhap_kho_id',
        'don_hang_chi_tiet_id',
        'so_luong_xuat',
        'ghi_chu',
    ];

    protected function casts(): array
    {
        return [
            'so_luong_xuat' => 'decimal:4',
        ];
    }

    public function phieuXuatKho(): BelongsTo
    {
        return $this->belongsTo(PhieuXuatKho::class, 'phieu_xuat_kho_id');
    }

    public function nhapKho(): BelongsTo
    {
        return $this->belongsTo(NhapKho::class, 'nhap_kho_id');
    }

    public function donHangChiTiet(): BelongsTo
    {
        return $this->belongsTo(DonHangChiTiet::class, 'don_hang_chi_tiet_id');
    }
}
