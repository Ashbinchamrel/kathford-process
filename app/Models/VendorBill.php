<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorBill extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids;

    protected $fillable = [
        'purchase_order_id', 'vendor_id', 'bill_number', 'bill_date',
        'amount', 'tax_amount', 'notes', 'disk_path', 'original_name',
        'mime_type', 'file_size', 'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function procurementChecklist(): HasOne
    {
        return $this->hasOne(ProcurementChecklist::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorBillItem::class);
    }
}
