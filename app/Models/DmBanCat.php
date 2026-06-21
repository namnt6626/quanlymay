<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmBanCat extends Model
{
    use SoftDeletes;

    protected $table = 'dm_ban_cat';

    protected $fillable = [
        'ma_ban',
        'ten_ban',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'trang_thai' => 'boolean',
        ];
    }
}
