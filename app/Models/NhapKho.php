<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NhapKho extends Model
{
    use SoftDeletes;

    protected $table = 'nhap_kho';

    protected $fillable = [
        'qc_id',
        'don_hang_chi_tiet_id',
        'ngay_nhap',
        'so_luong_nhap',
        'loai_ton',
        'auto_from_qc',
        'ghi_chu',
    ];

    protected function casts(): array
    {
        return [
            'ngay_nhap' => 'date',
            'so_luong_nhap' => 'decimal:4',
            'auto_from_qc' => 'boolean',
        ];
    }

    public function qc(): BelongsTo
    {
        return $this->belongsTo(Qc::class, 'qc_id');
    }

    public function donHangChiTiet(): BelongsTo
    {
        return $this->belongsTo(DonHangChiTiet::class, 'don_hang_chi_tiet_id');
    }

    public function phieuXuatKhoChiTiets(): HasMany
    {
        return $this->hasMany(PhieuXuatKhoChiTiet::class, 'nhap_kho_id');
    }
}
