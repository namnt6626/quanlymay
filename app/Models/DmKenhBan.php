<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class DmKenhBan extends Model
{
    use SoftDeletes;

    protected $table = 'dm_kenh_ban';

    protected $fillable = [
        'ma_kenh',
        'ten_kenh',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'trang_thai' => 'boolean',
        ];
    }

    public static function activeNames(): Collection
    {
        static $names = null;

        if ($names !== null) {
            return $names;
        }

        return $names = self::query()
            ->where('trang_thai', true)
            ->orderBy('ten_kenh')
            ->pluck('ten_kenh');
    }

    public static function activeNameRule(): Exists
    {
        return Rule::exists((new self)->getTable(), 'ten_kenh')
            ->where('trang_thai', true)
            ->whereNull('deleted_at');
    }
}
