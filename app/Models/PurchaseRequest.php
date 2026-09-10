<?php

namespace App\Models;

use App\Models\FormCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'form_number', 'title', 'description', 'category_id', 'creator_id', 'department_id', 'activity_form_id',
        'activity_name', 'deadline_date', 'remarks', 'total_amount', 'status',
        'approval_chain_id',
        'verifier_id', 'verifier_decision', 'verifier_note', 'verified_at',
        'approver_id', 'approver_decision', 'approver_note', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline_date' => 'date',
            'verified_at'   => 'datetime',
            'approved_at'   => 'datetime',
            'total_amount'  => 'decimal:2',
        ];
    }

    public function category(): BelongsTo  { return $this->belongsTo(FormCategory::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'creator_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function activityForm(): BelongsTo { return $this->belongsTo(ActivityForm::class); }
    public function approvalChain(): BelongsTo { return $this->belongsTo(ApprovalChain::class); }
    public function verifier(): BelongsTo  { return $this->belongsTo(User::class, 'verifier_id'); }
    public function verifierUser(): BelongsTo { return $this->belongsTo(User::class, 'verifier_id'); }
    public function approverUser(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }
    public function approver(): BelongsTo  { return $this->belongsTo(User::class, 'approver_id'); }

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

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeForUser($query, $user)
    {
        if ($user->isSuperAdmin() || $user->hasAnyRole(["finance", "approver", "verifier"])) {
            return $query;
        }
        return $query->where("creator_id", $user->id);
    }

    public function recalculateTotal(): void
    {
        $total = $this->lineItems()->sum('amount');
        $this->update(['total_amount' => $total]);
    }

    // ── Status helpers ─────────────────────────────────────────
    public function isEditable(): bool          { return in_array($this->status, ['draft']); }
    public function isPendingVerification(): bool { return $this->status === 'pending_verification'; }
    public function isPendingApproval(): bool    { return $this->status === 'pending_approval'; }
    public function isApproved(): bool           { return $this->status === 'approved'; }

    public function statusColor(): string
    {
        return match($this->status) {
            'approved'             => 'green',
            'rejected'             => 'red',
            'pending_verification',
            'pending_approval'     => 'yellow',
            'verified'             => 'blue',
            default                => 'gray',
        };
    }
}
