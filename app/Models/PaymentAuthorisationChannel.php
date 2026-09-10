<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAuthorisationChannel extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'approval_chain_id', 'payment_account_id', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function approvalChain(): BelongsTo { return $this->belongsTo(ApprovalChain::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function schedules(): HasMany { return $this->hasMany(Payment::class, 'payment_authorisation_channel_id'); }
    public function authorisations(): HasMany { return $this->hasMany(PaymentAuthorisation::class, 'payment_authorisation_channel_id'); }
    public function scopeActive($query) { return $query->where('is_active', true); }
}
