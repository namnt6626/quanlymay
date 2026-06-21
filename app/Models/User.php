<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected array $permissionCache = [];

    protected ?bool $adminCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role_id',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'status' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permissionCode): bool
    {
        if (($this->permissionCache[$permissionCode] ?? null) !== null) {
            return $this->permissionCache[$permissionCode];
        }

        if ($this->isAdmin()) {
            return $this->permissionCache[$permissionCode] = true;
        }

        $this->loadMissing('role.permissions');

        $hasPermission = $this->role?->permissions?->contains('ma_quyen', $permissionCode) ?? false;

        return $this->permissionCache[$permissionCode] = $hasPermission;
    }

    public function isAdmin(): bool
    {
        if ($this->adminCache !== null) {
            return $this->adminCache;
        }

        $this->loadMissing('role');

        return $this->adminCache = $this->role?->ma_vai_tro === 'ADMIN';
    }
}
