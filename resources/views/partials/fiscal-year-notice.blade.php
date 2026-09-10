@if(isset($fiscalYearWritable) && !$fiscalYearWritable)
<div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" role="status">
    @if($workingFiscalYear)
        Viewing {{ $workingFiscalYear->name }} · Read-only. Select the active year to create or change transactions.
        @if($workingFiscalYear->is_legacy) These older entries had no reliable budget-year link and have been preserved here. @endif
    @else
        No active fiscal year is configured. Set up a fiscal year under Organisation Profile before creating transactions.
    @endif
</div>
@endif
