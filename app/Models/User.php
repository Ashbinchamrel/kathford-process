<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public ?array $_permissionKeys = null;

    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'dashboard_widgets', 'name', 'email', 'password', 'google_id', 'avatar',
        'role_id', 'department_id', 'phone', 'designation',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'is_active', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = [
        'password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'dashboard_widgets' => 'array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at'           => 'datetime',
            'is_active'               => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function activityForms(): HasMany
    {
        return $this->hasMany(ActivityForm::class, 'creator_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function appUnreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->where('is_read', false);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('granted_by', 'granted_at');
    }

    // ── Role helpers ─────────────────────────────────────────

    public function hasRole(string $roleName): bool
    {
        return $this->role?->name === $roleName;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role?->name, $roles);
    }

    public function isSuperAdmin(): bool  { return $this->hasRole('super_admin'); }
    public function isVerifier(): bool    { return $this->hasRole('verifier'); }
    public function isApprover(): bool    { return $this->hasRole('approver'); }
    public function isFinance(): bool     { return $this->hasRole('finance'); }
    public function isGeneral(): bool     { return $this->hasRole('general'); }

    public function canApprove(): bool
    {
        return $this->hasAnyRole(['super_admin', 'verifier', 'approver']);
    }

    // ── 2FA helpers ──────────────────────────────────────────

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->whereHas('role', fn($q) => $q->where('name', $role));
    }
}
