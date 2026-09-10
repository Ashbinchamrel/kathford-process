<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'purchase_order_id', 'description', 'quantity', 'unit', 'unit_rate', 'total',
        'rfq_quote_item_id', 'request_remarks',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function rfqQuoteItem(): BelongsTo
    {
        return $this->belongsTo(RfqQuoteItem::class);
    }

    public function vendorBillItems(): HasMany
    {
        return $this->hasMany(VendorBillItem::class);
    }

    public function invoicedQuantity(): float
    {
        return (float) $this->vendorBillItems()->whereHas('vendorBill', fn ($bill) => $bill->where('status', '!=', 'returned'))->sum('quantity');
    }
}
