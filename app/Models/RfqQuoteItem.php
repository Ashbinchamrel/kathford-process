<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqQuoteItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'rfq_item_id',
        'rfq_quote_id',
        'line_item_id',
        'description',
        'quantity',
        'unit',
        'request_remarks',
        'unit_rate',
        'total',
        'notes',
        'award_status',
        'accepted_by',
        'accepted_at',
        'negotiation_message',
        'negotiation_requested_by',
        'negotiation_requested_at',
    ];

    protected $casts = [
        'quantity'  => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'total'     => 'decimal:2',
        'accepted_at' => 'datetime',
        'negotiation_requested_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->total = (float) $item->quantity * (float) $item->unit_rate;
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(RfqQuote::class, 'rfq_quote_id');
    }

    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(FormLineItem::class, 'line_item_id');
    }

    public function purchaseOrderItem(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PurchaseOrderItem::class);
    }
}
