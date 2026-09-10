<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentAuthorisation extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'authorisation_number', 'schedule_month', 'schedule_week', 'total_amount', 'notes', 'created_by',
        'approval_chain_id', 'payment_authorisation_channel_id', 'status', 'verifier_id', 'verifier_decision', 'verifier_note', 'verified_at',
        'approver_id', 'approver_decision', 'approver_note', 'approved_at', 'csv_exported_at',
    ];

    protected function casts(): array
    {
        return ['schedule_month' => 'date', 'total_amount' => 'decimal:2', 'verified_at' => 'datetime', 'approved_at' => 'datetime', 'csv_exported_at' => 'datetime'];
    }

    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approvalChain(): BelongsTo { return $this->belongsTo(ApprovalChain::class); }
    public function paymentAuthorisationChannel(): BelongsTo { return $this->belongsTo(PaymentAuthorisationChannel::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verifier_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function approvalActions(): MorphMany { return $this->morphMany(ApprovalAction::class, 'actionable')->orderBy('acted_at'); }

    public function isEditable(): bool { return in_array($this->status, ['generated', 'draft', 'rejected']); }
    public function isPendingVerification(): bool { return $this->status === 'pending_verification'; }
    public function isPendingApproval(): bool { return $this->status === 'pending_approval'; }
    public function statusLabel(): string { return match ($this->status) { 'generated' => 'Generated', 'draft' => 'Returned for Modification', 'pending_verification' => 'Awaiting Verification', 'pending_approval' => 'Awaiting Approval', 'approved' => 'Approved', 'rejected' => 'Returned / Rejected', default => ucfirst(str_replace('_', ' ', $this->status)), }; }
}
