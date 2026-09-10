<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBillItem extends Model
{
    use HasUuids;

    protected $fillable = ['vendor_bill_id', 'purchase_order_item_id', 'quantity', 'unit_rate', 'total'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'unit_rate' => 'decimal:2', 'total' => 'decimal:2']; }
    public function vendorBill(): BelongsTo { return $this->belongsTo(VendorBill::class); }
    public function purchaseOrderItem(): BelongsTo { return $this->belongsTo(PurchaseOrderItem::class); }
}
