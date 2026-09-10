<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceivedItem extends Model
{
    use HasUuids;

    protected $table = 'grn_items';

    protected $fillable = [
        'grn_id',
        'line_item_id',
        'ordered_quantity',
        'received_quantity',
        'item_condition_note',
    ];

    protected $casts = [
        'ordered_quantity'  => 'decimal:2',
        'received_quantity' => 'decimal:2',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(FormLineItem::class, 'line_item_id');
    }

    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->ordered_quantity;
    }
}
