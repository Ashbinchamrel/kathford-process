<?php

namespace App\Support;

use App\Models\FiscalYear;
use Illuminate\Validation\ValidationException;

/** Request-scoped year selection. CLI maintenance has no implicit filter. */
class FiscalYearContext
{
    public ?FiscalYear $year = null;
    public bool $enabled = false;
    public ?int $activeId = null;

    public function writable(): bool
    {
        return $this->year && !$this->year->is_legacy && $this->year->id === $this->activeId;
    }

    public function assertWritable(): void
    {
        if ($this->enabled && !$this->writable()) {
            throw ValidationException::withMessages(['fiscal_year' => 'This fiscal year is read-only. Select the active fiscal year to make changes.']);
        }
    }
}
