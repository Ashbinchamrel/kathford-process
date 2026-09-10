<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    // Append-only — no update or delete via Eloquent
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_email', 'action', 'model_type', 'model_id',
        'model_label', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'session_id', 'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'logged_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Convenience static factory
    public static function record(
        ?User $user,
        string $action,
        ?Model $model = null,
        ?string $label = null,
        array $oldValues = [],
        array $newValues = [],
    ): self {
        return self::create([
            'user_id'    => $user?->id,
            'user_email' => $user?->email,
            'action'     => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $model?->id,
            'model_label'=> $label,
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'logged_at'  => now(),
        ]);
    }
}
