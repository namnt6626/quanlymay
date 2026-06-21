<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmDonViMay extends Model
{
    use SoftDeletes;

    protected $table = 'dm_don_vi_may';

    protected $fillable = [
        'ma_don_vi',
        'ten_don_vi',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'trang_thai' => 'boolean',
        ];
    }
}
