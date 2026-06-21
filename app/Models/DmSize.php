<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmSize extends Model
{
    use SoftDeletes;

    protected $table = 'dm_size';

    protected $fillable = [
        'ma_size',
        'ten_size',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'trang_thai' => 'boolean',
        ];
    }
}
