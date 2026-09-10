<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalAction extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'actionable_type',
        'actionable_id',
        'actor_id',
        'layer',
        'decision',
        'note',
        'changes',
        'acted_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes'  => 'array',
        'acted_at' => 'datetime',
        'layer'    => 'integer',
    ];

    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function layerLabel(): string
    {
        if ($this->layer >= 200) {
            return 'Approver layer '.($this->layer - 199);
        }

        if ($this->layer >= 100) {
            return 'Verifier layer '.($this->layer - 99);
        }

        return match ($this->layer) {
            1 => 'Submitted',
            2 => 'Verified',
            3 => 'Approved',
            default => 'Action',
        };
    }

    public function decisionColor(): string
    {
        return match ($this->decision) {
            'approved', 'verified' => 'green',
            'rejected'             => 'red',
            'submitted'            => 'blue',
            default                => 'gray',
        };
    }
}
