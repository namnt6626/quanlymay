<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HangHoanOnlineChiTiet extends Model
{
    use SoftDeletes;

    protected $table = 'hang_hoan_online_chi_tiet';

    protected $fillable = [
        'hang_hoan_online_id',
        'return_order_id',
        'order_id',
        'sku_id',
        'seller_sku',
        'ten_san_pham',
        'mau',
        'size',
        'sku_name',
        'so_luong_hoan',
        'return_type',
        'return_status',
        'tinh_trang_hang',
        'cong_ton',
        'time_requested',
        'refund_time',
        'return_reason',
        'tracking_id',
        'compensation_status',
        'compensation_amount',
        'buyer_note',
        'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'so_luong_hoan' => 'decimal:4',
            'compensation_amount' => 'decimal:2',
            'cong_ton' => 'boolean',
            'time_requested' => 'datetime',
            'refund_time' => 'datetime',
        ];
    }

    public function hangHoanOnline(): BelongsTo
    {
        return $this->belongsTo(HangHoanOnline::class, 'hang_hoan_online_id');
    }
}
