<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'po_number', 'purchase_request_id', 'rfq_quote_id', 'vendor_id',
        'generated_by', 'approval_chain_id', 'delivery_address', 'expected_delivery_date',
        'terms_and_conditions', 'subtotal', 'tax_applied', 'tax_rate', 'tax_amount', 'total_amount',
        'status', 'sent_to_vendor_at', 'authorised_by_name', 'authorised_at',
        'verifier_id', 'verifier_decision', 'verifier_note', 'verified_at',
        'approver_id', 'approver_decision', 'approver_note', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_delivery_date' => 'date',
            'sent_to_vendor_at'      => 'datetime',
            'authorised_at'          => 'datetime',
            'verified_at'            => 'datetime',
            'approved_at'            => 'datetime',
            'subtotal'               => 'decimal:2',
            'tax_applied'            => 'boolean',
            'tax_rate'               => 'decimal:2',
            'tax_amount'             => 'decimal:2',
            'total_amount'           => 'decimal:2',
        ];
    }

    public function purchaseRequest(): BelongsTo { return $this->belongsTo(PurchaseRequest::class); }
    public function rfqQuote(): BelongsTo { return $this->belongsTo(RfqQuote::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function generatedBy(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
    public function approvalChain(): BelongsTo { return $this->belongsTo(ApprovalChain::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verifier_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }

    public function goodsReceived(): HasMany { return $this->hasMany(GoodsReceivedNote::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function vendorBills(): HasMany { return $this->hasMany(VendorBill::class); }
    public function procurementChecklists(): HasMany { return $this->hasMany(ProcurementChecklist::class); }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FormAttachment::class, 'attachable');
    }

    public function approvalActions(): MorphMany
    {
        return $this->morphMany(ApprovalAction::class, 'actionable')->orderBy('acted_at');
    }

    public function isEditable(): bool { return in_array($this->status, ['generated', 'rejected']); }
    public function isPendingVerification(): bool { return $this->status === 'pending_verification'; }
    public function isPendingApproval(): bool { return $this->status === 'pending_approval'; }
    public function isApproved(): bool { return $this->status === 'approved'; }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'generated' => 'Generated',
            'pending_verification' => 'Awaiting Verification',
            'pending_approval' => 'Awaiting Approval',
            'approved' => 'Approved',
            'sent_to_vendor' => 'Issued to Vendor',
            'goods_pending' => 'Goods Pending',
            'partially_received' => 'Partially Received',
            'fully_received' => 'Fully Received',
            'rejected' => 'Returned / Rejected',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->where('status', 'paid')->sum('amount_paid');
    }

    public function balance(): float
    {
        return (float) $this->total_amount - $this->totalPaid();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** A PO completes only when every approved line has been invoiced in full. */
    public function allItemsInvoiced(): bool
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->with('vendorBillItems')->get();
        return $items->isNotEmpty() && $items->every(fn (PurchaseOrderItem $item) => $item->invoicedQuantity() + 0.0001 >= (float) $item->quantity);
    }
}
