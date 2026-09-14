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

    /** Standalone (non-RFQ) manual Purchase Orders tracked directly against this budget. */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'budget_id');
    }

    private const PO_RESERVED_STATUSES = [
        'pending_verification', 'pending_approval', 'approved',
        'sent_to_vendor', 'goods_pending', 'partially_received', 'fully_received',
    ];

    /** Amount committed by submitted, verified, or approved activity forms and standalone POs. */
    public function reservedAmount(?string $excludingFormId = null, ?string $excludingPoId = null): float
    {
        $formQuery = $this->activityForms()
            ->whereIn('status', [
                ActivityForm::STATUS_PENDING_VERIFICATION,
                ActivityForm::STATUS_PENDING_APPROVAL,
                ActivityForm::STATUS_APPROVED,
            ]);

        if ($excludingFormId) {
            $formQuery->whereKeyNot($excludingFormId);
        }

        $poQuery = $this->purchaseOrders()->whereIn('status', self::PO_RESERVED_STATUSES);

        if ($excludingPoId) {
            $poQuery->whereKeyNot($excludingPoId);
        }

        return (float) $formQuery->sum('total_estimated_amount') + (float) $poQuery->sum('total_amount');
    }

    public function remainingAmount(?string $excludingFormId = null, ?string $excludingPoId = null): float
    {
        return (float) $this->allocated_amount - $this->reservedAmount($excludingFormId, $excludingPoId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
