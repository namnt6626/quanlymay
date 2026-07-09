<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NhapHangOnlineChiTiet extends Model
{
    use SoftDeletes;

    protected $table = 'nhap_hang_online_chi_tiet';

    protected $fillable = ['nhap_hang_online_id', 'ten_san_pham', 'mau', 'size', 'so_luong', 'don_gia', 'thanh_tien'];

    protected function casts(): array
    {
        return [
            'so_luong' => 'decimal:4',
            'don_gia' => 'decimal:2',
            'thanh_tien' => 'decimal:2',
        ];
    }

    public function nhapHangOnline(): BelongsTo
    {
        return $this->belongsTo(NhapHangOnline::class, 'nhap_hang_online_id');
    }
}
