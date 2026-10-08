<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'company_id', 'branch_id', 'role_id', 'name', 'email', 'password', 'phone',
        'is_super_admin', 'is_owner', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'is_super_admin' => false,
        'is_owner' => false,
        'is_active' => true,
    ];

    protected ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_owner' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withoutGlobalScopes();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions(): array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        return $this->permissionCache = $this->role?->permissions ?? [];
    }

    public function hasPermission(string $ability): bool
    {
        if (! $this->company_id || ! $this->is_active) {
            return false;
        }

        if ($this->is_owner) {
            return true;
        }

        return in_array($ability, $this->permissions(), true);
    }

    public function roleName(): string
    {
        if ($this->is_super_admin) {
            return 'Super administrador';
        }

        return $this->is_owner ? 'Propietario' : ($this->role?->name ?? 'Sin rol');
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }
}
