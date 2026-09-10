<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormCategory extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'category_group',
        'requires_reason', 'requires_logistic_table', 'requires_attachments',
        'requires_vendor_selection', 'auto_generate_next',
        'extra_fields', 'approval_chain_id', 'sort_order', 'is_active', 'is_system',
        'bypasses_procurement_to_payment',
    ];

    protected function casts(): array
    {
        return [
            'requires_reason'            => 'boolean',
            'requires_logistic_table'    => 'boolean',
            'requires_attachments'       => 'boolean',
            'requires_vendor_selection'  => 'boolean',
            'auto_generate_next'         => 'boolean',
            'is_active'                  => 'boolean',
            'is_system'                  => 'boolean',
            'bypasses_procurement_to_payment' => 'boolean',
            'extra_fields'               => 'array',
        ];
    }

    public function approvalChain(): BelongsTo
    {
        return $this->belongsTo(ApprovalChain::class);
    }

    public function forms(): HasMany
    {
        return $this->hasMany(ActivityForm::class, 'category_id');
    }

    // Resolve which chain to use: category-specific, or default
    public function resolveChain(): ?ApprovalChain
    {
        if ($this->approval_chain_id) {
            return $this->approvalChain;
        }
        return ApprovalChain::where('is_default', true)->where('is_active', true)->first();
    }

    // Generate the next form number: CODE-YYYY-NNNN
    public function nextFormNumber(): string
    {
        $year  = now()->setTimezone('Asia/Kathmandu')->year; // AD year; can swap to BS if needed
        $count = $this->forms()->withoutGlobalScope('fiscal_year')->withTrashed()->whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%d-%04d', strtoupper($this->code), $year, $count);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /** Every activity form takes its Title / Subject from a departmental budget activity. */
    public function usesDepartmentBudget(): bool
    {
        return true;
    }

    // Validate extra_fields schema
    public function validatedExtraFields(): array
    {
        $fields = $this->extra_fields ?? [];
        return array_filter($fields, fn($f) =>
            isset($f['name'], $f['label'], $f['type'])
        );
    }
}
