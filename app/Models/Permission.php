<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'ma_quyen',
    'ten_quyen',
    'module',
    'action',
    'mo_ta',
    'trang_thai',
  ];

  protected function casts(): array
  {
    return [
      'trang_thai' => 'boolean',
    ];
  }

  public function roles(): BelongsToMany
  {
    return $this->belongsToMany(Role::class, 'role_permission')
      ->withTimestamps();
  }
}
