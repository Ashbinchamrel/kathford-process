<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementChecklist extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $checklist) {
            $checklist->question_snapshot ??= ChecklistQuestion::snapshot($checklist->fulfillment_type ?: 'goods');
        });
    }

    protected $fillable = [
        'question_snapshot', 'answers', 'vendor_bill_id', 'purchase_order_id', 'vendor_id', 'fulfillment_type',
        'gate_entry_checked', 'received_checked', 'quality_checked',
        'invoice_received_checked', 'store_entry_checked', 'job_completion_checked',
        'control_comments', 'status', 'completed_by', 'completed_at',
        'accounts_sent_by', 'accounts_sent_at', 'accounts_comment',
    ];

    protected function casts(): array
    {
        return [
            'question_snapshot'=>'array', 'answers'=>'array',
            'gate_entry_checked' => 'boolean',
            'received_checked' => 'boolean',
            'quality_checked' => 'boolean',
            'invoice_received_checked' => 'boolean',
            'store_entry_checked' => 'boolean',
            'job_completion_checked' => 'boolean',
            'completed_at' => 'datetime',
            'accounts_sent_at' => 'datetime',
        ];
    }

    public function vendorBill(): BelongsTo { return $this->belongsTo(VendorBill::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
    public function accountsSentBy(): BelongsTo { return $this->belongsTo(User::class, 'accounts_sent_by'); }
    public function checklistPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'vendor_bill_id', 'vendor_bill_id')
            ->where('source', 'checklist')
            ->whereNull('parent_payment_id');
    }

    public function isReadyForAccounts(): bool
    {
        return $this->status === 'ready_for_accounts';
    }

    public function isSentToAccounts(): bool
    {
        return $this->status === 'sent_to_accounts';
    }

    public function requiredChecks(): array
    {
        if ($this->question_snapshot !== null) return collect($this->question_snapshot)->mapWithKeys(fn($q,$key)=>[$key=>$q['label']])->all();
        $checks = [
            'gate_entry_checked' => 'Gate entry confirmed',
            'received_checked' => $this->fulfillment_type === 'service' ? 'Service received' : 'Goods received',
            'quality_checked' => 'Quality checked',
            'invoice_received_checked' => 'Vendor invoice received',
        ];

        $checks[$this->fulfillment_type === 'service' ? 'job_completion_checked' : 'store_entry_checked'] = $this->fulfillment_type === 'service'
            ? 'Job completion confirmed'
            : 'Store entry completed';

        return $checks;
    }

    public function controlsComplete(): bool
    {
        if ($this->question_snapshot !== null) {
            if (!$this->question_snapshot) return false;
            foreach ($this->question_snapshot as $key=>$question) if ($question['required'] && !($this->answers[$key]??false)) return false;
            return true;
        }
        foreach (array_keys($this->requiredChecks()) as $field) {
            if (! $this->{$field}) return false;
        }
        return true;
    }

    public function answerChecked(string $key): bool { return $this->question_snapshot !== null ? (bool)($this->answers[$key]??false) : (bool)$this->{$key}; }
    public function returns() { return $this->hasMany(ProcurementReturn::class,'checklist_id'); }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_controls' => 'Pending checklist',
            'ready_for_accounts' => 'Ready for accounts',
            'sent_to_accounts' => 'Sent to accounts',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
