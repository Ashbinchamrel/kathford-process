<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'assigned_to', 'budget_id', 'handoff_activity_id', 'preparation_items', 'rfq_number', 'title', 'purchase_request_id', 'activity_form_id', 'created_by', 'status', 'deadline', 'notes',
    ];

    protected function casts(): array
    {
        return ['deadline' => 'date', 'preparation_items' => 'array'];
    }

    public function requestItems() { return $this->hasMany(RfqItem::class); }
    public function budget() { return $this->belongsTo(DepartmentBudget::class); }
    public function purchaseRequest(): BelongsTo { return $this->belongsTo(PurchaseRequest::class); }
    public function activityForm(): BelongsTo { return $this->belongsTo(ActivityForm::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function quotes(): HasMany { return $this->hasMany(RfqQuote::class); }

    public function acceptedQuote(): ?RfqQuote
    {
        return $this->quotes()->where('status', 'accepted')->first();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'sent' => 'Invitations Sent',
            'quotes_received' => 'Quotations Received',
            'partially_awarded' => 'Partially Awarded',
            'items_awarded' => 'All Items Awarded',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
