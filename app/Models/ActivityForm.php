<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityForm extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'form_number', 'category_id', 'creator_id', 'department_id', 'budget_id',
        'activity_name', 'deadline_date', 'remarks', 'unplanned_reason',
        'extra_field_values', 'status', 'approval_chain_id',
        'verifier_id', 'verifier_decision', 'verifier_note', 'verified_at',
        'approver_id', 'approver_decision', 'approver_note', 'approved_at',
        'total_estimated_amount', 'budget_exception', 'budget_exception_reason',
        'budget_checked_at', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'deadline_date'      => 'date',
            'verified_at'        => 'datetime',
            'approved_at'        => 'datetime',
            'extra_field_values' => 'array',
            'total_estimated_amount' => 'decimal:2',
            'budget_exception'       => 'boolean',
            'budget_checked_at'      => 'datetime',
        ];
    }

    // Status constants
    const STATUS_DRAFT                = 'draft';
    const STATUS_SUBMITTED            = 'submitted';
    const STATUS_PENDING_VERIFICATION = 'pending_verification';
    const STATUS_VERIFIED             = 'verified';
    const STATUS_PENDING_APPROVAL     = 'pending_approval';
    const STATUS_APPROVED             = 'approved';
    const STATUS_REJECTED             = 'rejected';

    // ── Relationships ────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(FormCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(DepartmentBudget::class, 'budget_id');
    }

    public function approvalChain(): BelongsTo
    {
        return $this->belongsTo(ApprovalChain::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function lineItems(): MorphMany
    {
        return $this->morphMany(FormLineItem::class, 'itemable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FormAttachment::class, 'attachable');
    }

    public function approvalActions(): MorphMany
    {
        return $this->morphMany(ApprovalAction::class, 'actionable')->orderBy('acted_at');
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'activity_form_id');
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    public function isPendingVerification(): bool
    {
        return $this->status === self::STATUS_PENDING_VERIFICATION;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT                => 'Draft',
            self::STATUS_SUBMITTED            => 'Submitted',
            self::STATUS_PENDING_VERIFICATION => 'Awaiting Verification',
            self::STATUS_VERIFIED             => 'Verified',
            self::STATUS_PENDING_APPROVAL     => 'Awaiting Approval',
            self::STATUS_APPROVED             => 'Approved',
            self::STATUS_REJECTED             => 'Rejected',
            default                           => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            self::STATUS_APPROVED             => 'green',
            self::STATUS_REJECTED             => 'red',
            self::STATUS_PENDING_VERIFICATION,
            self::STATUS_PENDING_APPROVAL     => 'yellow',
            self::STATUS_VERIFIED             => 'blue',
            default                           => 'gray',
        };
    }

    // Recalculate total from line items
    public function recalculateTotal(): void
    {
        $total = $this->lineItems()->sum('amount');
        $this->updateQuietly(['total_estimated_amount' => $total]);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeForUser($query, User $user)
    {
        return \App\Support\RecordVisibility::apply($query, $user);
    }
}
