<?php

namespace App\Models\Concerns;

use App\Support\FiscalYearContext;
use App\Support\FiscalYearRecords;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait BelongsToFiscalYear
{
    public static function bootBelongsToFiscalYear(): void
    {
        static::addGlobalScope('fiscal_year', function (Builder $query) {
            $context = app(FiscalYearContext::class);
            if ($context->enabled) {
                $query->where($query->getModel()->qualifyColumn('fiscal_year_id'), $context->year?->id ?? 0);
            }
        });

        static::saving(function ($record) {
            $context = app(FiscalYearContext::class);
            $context->assertWritable();
            $yearId = $record->exists ? $record->getOriginal('fiscal_year_id') : ($context->year?->id ?? $record->fiscal_year_id);
            if ($record->exists && $record->isDirty('fiscal_year_id')) {
                throw ValidationException::withMessages(['fiscal_year' => 'Existing entries cannot be moved to another fiscal year.']);
            }
            foreach (FiscalYearRecords::PARENTS[$record->getTable()] ?? [] as $key => $table) {
                if (!$record->$key) continue;
                $parentYear = DB::table($table)->where('id', $record->$key)->value('fiscal_year_id');
                if (!$parentYear || ($yearId && (int) $parentYear !== (int) $yearId)) {
                    throw ValidationException::withMessages([$key => 'Choose a source entry from the same fiscal year.']);
                }
                $yearId ??= $parentYear;
            }
            if (!$yearId) {
                throw ValidationException::withMessages(['fiscal_year' => 'Select a fiscal year before creating an entry.']);
            }
            if ($context->enabled && (int) $yearId !== $context->year?->id) {
                throw ValidationException::withMessages(['fiscal_year' => 'This entry belongs to another fiscal year.']);
            }
            $record->fiscal_year_id = $yearId;
            if ($record->getTable() === 'department_budgets') {
                $record->fiscal_year = FiscalYear::findOrFail($yearId)->name;
            }
        });
        static::deleting(fn () => app(FiscalYearContext::class)->assertWritable());
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
