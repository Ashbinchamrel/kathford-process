<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ApprovalMemberRole implements ValidationRule
{
    public function __construct(private readonly string $role) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !User::active()->withRole($this->role)->whereKey($value)->exists()) {
            $fail('Select an active user with the '.ucfirst($this->role).' role.');
        }
    }
}
