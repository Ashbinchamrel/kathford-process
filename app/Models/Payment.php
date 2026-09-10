<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Payment extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (self $payment) {
            $billId = $payment->vendor_bill_id ?: $payment->parentPayment?->vendor_bill_id;
            if ($payment->status !== 'cancelled' && $billId && VendorBill::whereKey($billId)->where('status', 'returned')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'This invoice was returned to the vendor and cannot be scheduled or paid.']);
            }
        });
    }

    protected $fillable = [
        'payment_number', 'purchase_order_id', 'activity_form_id', 'parent_payment_id', 'vendor_id', 'payee_id', 'payment_account_id', 'vendor_bill_id', 'payment_authorisation_id', 'payment_authorisation_channel_id', 'created_by', 'source', 'payment_type',
        'tds_applied', 'tds_rate', 'tds_amount', 'po_total', 'amount_due', 'net_amount', 'amount_paid',
        'scheduled_date', 'schedule_month', 'schedule_week', 'actual_date', 'payment_method', 'bill_number', 'activity_name', 'activity_reference', 'account_name', 'sub_account',
        'payment_reference', 'bank_name', 'bank_account_number',
        'status', 'notes', 'marked_paid_by', 'marked_paid_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date'  => 'date',
            'schedule_month'  => 'date',
            'actual_date'     => 'date',
            'marked_paid_at'  => 'datetime',
            'po_total'        => 'decimal:2',
            'amount_due'      => 'decimal:2',
            'net_amount'      => 'decimal:2',
            'tds_applied'     => 'boolean',
            'tds_rate'        => 'decimal:2',
            'tds_amount'      => 'decimal:2',
            'amount_paid'     => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function activityForm(): BelongsTo { return $this->belongsTo(ActivityForm::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function vendorBill(): BelongsTo { return $this->belongsTo(VendorBill::class); }
    public function setBankAccountNumberAttribute(?string $value): void { $this->attributes['bank_account_number'] = $value ? Crypt::encryptString($value) : null; }
    public function getBankAccountNumberAttribute(?string $value): ?string { if (! $value) return null; try { return Crypt::decryptString($value); } catch (\Throwable) { return $value; } }
    public function payee(): BelongsTo { return $this->belongsTo(Payee::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function parentPayment(): BelongsTo { return $this->belongsTo(Payment::class, 'parent_payment_id'); }
    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Payment::class, 'parent_payment_id'); }
    public function paymentAuthorisation(): BelongsTo { return $this->belongsTo(PaymentAuthorisation::class); }
    public function paymentAuthorisationChannel(): BelongsTo { return $this->belongsTo(PaymentAuthorisationChannel::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function markedPaidBy(): BelongsTo { return $this->belongsTo(User::class, 'marked_paid_by'); }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FormAttachment::class, 'attachable');
    }

    public function isDue(): bool
    {
        return $this->status === 'scheduled'
            && $this->scheduled_date->lte(now()->addDays(3));
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'paid'      => 'green',
            'cancelled' => 'red',
            'processing'=> 'blue',
            'pending_finance' => 'yellow',
            default     => 'yellow',
        };
    }
}
