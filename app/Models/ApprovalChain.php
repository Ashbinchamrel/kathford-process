<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ApprovalChain extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'is_default', 'notes', 'is_active',
        // verifier_id / approver_id kept for DB compat but managed via members table
        'verifier_id', 'approver_id',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ];
    }

    // ── Multi-user relationships (new) ───────────────────────

    public function verifiers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'approval_chain_members',
            'approval_chain_id',
            'user_id'
        )
        ->wherePivot('role', 'verifier')
        ->withPivot(['role', 'sort_order'])
        ->orderByPivot('sort_order');
    }

    public function approvers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'approval_chain_members',
            'approval_chain_id',
            'user_id'
        )
        ->wherePivot('role', 'approver')
        ->withPivot(['role', 'sort_order'])
        ->orderByPivot('sort_order');
    }

    // ── Helpers ──────────────────────────────────────────────

    public function hasVerifier(string $userId): bool
    {
        return $this->verifiers()->where('users.id', $userId)->exists();
    }

    public function hasApprover(string $userId): bool
    {
        return $this->approvers()->where('users.id', $userId)->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Sync verifiers — replaces all existing verifier members.
     */
    public function syncVerifiers(array $userIds): void
    {
        \DB::table('approval_chain_members')
            ->where('approval_chain_id', $this->id)
            ->where('role', 'verifier')
            ->delete();

        foreach (array_values($userIds) as $index => $userId) {
            \DB::table('approval_chain_members')->insert([
                'approval_chain_id' => $this->id,
                'user_id'           => $userId,
                'role'              => 'verifier',
                'sort_order'        => $index,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    /**
     * Sync approvers — replaces all existing approver members.
     */
    public function syncApprovers(array $userIds): void
    {
        \DB::table('approval_chain_members')
            ->where('approval_chain_id', $this->id)
            ->where('role', 'approver')
            ->delete();

        foreach (array_values($userIds) as $index => $userId) {
            \DB::table('approval_chain_members')->insert([
                'approval_chain_id' => $this->id,
                'user_id'           => $userId,
                'role'              => 'approver',
                'sort_order'        => $index,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
