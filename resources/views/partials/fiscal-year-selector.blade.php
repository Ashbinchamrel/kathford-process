@if(isset($workingFiscalYear))
<span class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600">Fiscal year {{ $workingFiscalYear->name }}{{ $fiscalYearWritable ? ' · Active' : ' · Read-only' }}</span>
@endif
