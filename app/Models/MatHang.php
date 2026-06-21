<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatHang extends Model
{
    use SoftDeletes;

    protected $table = 'dm_mat_hang';

    protected $fillable = [
        'ma_hang',
        'ten_hang',
        'mo_ta',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'trang_thai' => 'boolean',
        ];
    }
}
