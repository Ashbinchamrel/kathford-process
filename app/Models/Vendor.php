<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Vendor extends Model
{
    use HasUuids, SoftDeletes;

    protected $hidden = ['portal_password'];

    protected $fillable = [
        'name', 'is_active', 'category', 'pan_vat_number', 'company_type',
        'owner_name', 'contact_person', 'mobile_number', 'office_number',
        'address', 'email', 'bank_name', 'bank_account_name', 'bank_account_number',
        'created_by', 'updated_by', 'notes', 'portal_enabled', 'portal_password',
        'portal_password_changed_at', 'portal_last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                  => 'boolean',
            'portal_enabled'             => 'boolean',
            'portal_password_changed_at' => 'datetime',
            'portal_last_login_at'       => 'datetime',
        ];
    }

    // ── Encrypt/decrypt bank account number ──────────────────

    public function setBankAccountNumberAttribute(?string $value): void
    {
        $this->attributes['bank_account_number'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getBankAccountNumberAttribute(?string $value): ?string
    {
        if (! $value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null; // Corrupted or wrong key
        }
    }

    // Masked version for display
    public function maskedBankAccount(): string
    {
        $account = $this->bank_account_number;
        if (! $account) return '—';
        return str_repeat('*', max(0, strlen($account) - 4)) . substr($account, -4);
    }

    // ── Relationships ────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rfqQuotes(): HasMany
    {
        return $this->hasMany(RfqQuote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ── Helpers ──────────────────────────────────────────────

    public static function categories(): array
    {
        return [
            'Stationery', 'IT Equipment', 'Furniture', 'Services',
            'Catering', 'Construction', 'Transportation', 'Other',
        ];
    }

    public static function companyTypes(): array
    {
        return [
            'Sole Proprietor', 'Private Ltd', 'Public Ltd',
            'Partnership', 'NGO', 'Government', 'Other',
        ];
    }
}
