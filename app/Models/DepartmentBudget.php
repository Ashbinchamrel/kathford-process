<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartmentBudget extends Model
{
    use \App\Models\Concerns\BelongsToFiscalYear;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'department_id', 'fiscal_year', 'activity_title', 'allocated_amount',
        'notes', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activityForms(): HasMany
    {
        return $this->hasMany(ActivityForm::class, 'budget_id');
    }

    /** Amount committed by submitted, verified, or approved activity forms. */
    public function reservedAmount(?string $excludingFormId = null): float
    {
        $query = $this->activityForms()
            ->whereIn('status', [
                ActivityForm::STATUS_PENDING_VERIFICATION,
                ActivityForm::STATUS_PENDING_APPROVAL,
                ActivityForm::STATUS_APPROVED,
            ]);

        if ($excludingFormId) {
            $query->whereKeyNot($excludingFormId);
        }

        return (float) $query->sum('total_estimated_amount');
    }

    public function remainingAmount(?string $excludingFormId = null): float
    {
        return (float) $this->allocated_amount - $this->reservedAmount($excludingFormId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
