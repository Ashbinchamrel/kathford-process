<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'link',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markRead(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function icon(): string
    {
        return match ($this->type) {
            'form_submitted'   => 'document-text',
            'form_verified'    => 'check-circle',
            'form_approved'    => 'badge-check',
            'form_rejected'    => 'x-circle',
            'po_generated'     => 'clipboard-list',
            'grn_created'      => 'truck',
            'vendor_bill_submitted' => 'document-text',
            'checklist_sent_to_accounts' => 'check-circle',
            'payment_due'      => 'currency-dollar',
            default            => 'bell',
        };
    }
}
