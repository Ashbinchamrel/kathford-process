<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Payee extends Model
{
    use HasUuids;
    protected $fillable = ['name', 'type', 'bank_name', 'account_number', 'account_name', 'notes', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function setAccountNumberAttribute(?string $value): void { $this->attributes['account_number'] = $value ? Crypt::encryptString($value) : null; }
    public function getAccountNumberAttribute(?string $value): ?string { if (! $value) return null; try { return Crypt::decryptString($value); } catch (\Throwable) { return $value; } }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
