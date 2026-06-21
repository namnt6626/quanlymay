<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Qc extends Model
{
    use SoftDeletes;

    protected $table = 'qc';

    protected $fillable = [
        'phan_bo_may_id',
        'don_hang_chi_tiet_id',
        'mat_hang_id',
        'mau_id',
        'size_id',
        'ngay_qc',
        'so_luong_qc',
        'so_luong_dat',
        'so_luong_loi',
        'so_luong_hong',
        'ghi_chu',
    ];

    protected function casts(): array
    {
        return [
            'ngay_qc' => 'date',
            'so_luong_qc' => 'decimal:4',
            'so_luong_dat' => 'decimal:4',
            'so_luong_loi' => 'decimal:4',
            'so_luong_hong' => 'decimal:4',
        ];
    }

    public function phanBoMay(): BelongsTo
    {
        return $this->belongsTo(PhanBoMay::class, 'phan_bo_may_id');
    }

    public function donHangChiTiet(): BelongsTo
    {
        return $this->belongsTo(DonHangChiTiet::class, 'don_hang_chi_tiet_id');
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

    public function nhapKhos(): HasMany
    {
        return $this->hasMany(NhapKho::class, 'qc_id');
    }
}
