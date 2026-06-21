<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhieuXuatKho extends Model
{
    use SoftDeletes;

    protected $table = 'phieu_xuat_kho';

    protected $fillable = [
        'so_phieu',
        'ngay_xuat',
        'kenh_ban',
        'ghi_chu',
    ];

    protected function casts(): array
    {
        return [
            'ngay_xuat' => 'date',
        ];
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(PhieuXuatKhoChiTiet::class, 'phieu_xuat_kho_id');
    }
}
