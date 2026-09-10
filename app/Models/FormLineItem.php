<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormLineItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'itemable_type', 'itemable_id', 'item_name', 'quantity', 'unit',
        'rate', 'amount', 'item_remarks', 'vendor_id', 'quoted_rate', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity'    => 'decimal:3',
            'rate'        => 'decimal:2',
            'amount'      => 'decimal:2',
            'quoted_rate' => 'decimal:2',
        ];
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // Auto-calculate amount before save
    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->amount = round((float) $item->quantity * (float) $item->rate, 2);
        });
    }
}
