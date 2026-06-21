<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mau extends Model
{
    use SoftDeletes;

    protected $table = 'dm_mau';

    protected $fillable = [
        'ma_mau',
        'ten_mau',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'trang_thai' => 'boolean',
        ];
    }
}
