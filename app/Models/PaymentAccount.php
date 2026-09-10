<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    use HasUuids;
    protected $fillable = ['name', 'code', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
