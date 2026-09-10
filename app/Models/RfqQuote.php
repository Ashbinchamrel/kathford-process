<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class RfqQuote extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids;

    protected $fillable = [
        'rfq_id', 'vendor_id', 'vendor_token', 'token_expires_at', 'token_used',
        'entry_method', 'entered_by', 'status',
        'quote_date', 'valid_until', 'delivery_timeline', 'payment_terms',
        'notes', 'total_quoted', 'tax_applied', 'tax_rate', 'tax_amount', 'grand_total', 'submitted_at', 'accepted_by', 'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'token_used'       => 'boolean',
            'submitted_at'     => 'datetime',
            'accepted_at'      => 'datetime',
            'quote_date'       => 'date',
            'valid_until'      => 'date',
            'total_quoted'     => 'decimal:2',
            'tax_amount'       => 'decimal:2',
            'tax_applied'      => 'boolean',
            'tax_rate'         => 'decimal:2',
            'grand_total'      => 'decimal:2',
        ];
    }

    public function rfq(): BelongsTo { return $this->belongsTo(Rfq::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function enteredBy(): BelongsTo { return $this->belongsTo(User::class, 'entered_by'); }
    public function acceptedBy(): BelongsTo { return $this->belongsTo(User::class, 'accepted_by'); }
    public function items(): HasMany { return $this->hasMany(RfqQuoteItem::class); }

    public function generateToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'vendor_token'    => $token,
            'token_expires_at' => now()->addDays((int) config('kathford.rfq_link_expiry_days', 7)),
            'token_used'      => false,
        ]);
        return $token;
    }

    public function isTokenValid(): bool
    {
        return $this->vendor_token
            && ! $this->token_used
            && $this->token_expires_at?->isFuture();
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'rfq_quote_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'rfq_quote_id');
    }

    /** A vendor-facing status based on individual award decisions, not just quote submission. */
    public function vendorStatusLabel(): string
    {
        if ($this->status === 'accepted') return 'Quotation Accepted';
        if ($this->status === 'rejected') return 'Not Selected';

        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        $awarded = $items->where('award_status', 'accepted')->count();
        $rejected = $items->where('award_status', 'rejected')->count();

        if ($awarded > 0) return "Awarded ({$awarded} item".($awarded === 1 ? ')' : 's)');
        if ($items->isNotEmpty() && $rejected === $items->count()) return 'Not Selected';

        return match ($this->status) {
            'invited' => 'Quotation Requested',
            'submitted' => 'Quotation Submitted',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
