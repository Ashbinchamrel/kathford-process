<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'display_name', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // System roles that ship with the app
    const SUPER_ADMIN = 'super_admin';
    const VERIFIER    = 'verifier';
    const APPROVER    = 'approver';
    const FINANCE     = 'finance';
    const GENERAL     = 'general';
}
