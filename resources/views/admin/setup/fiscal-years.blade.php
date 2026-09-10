<section class="rounded-2xl border border-slate-200 bg-white shadow-sm" id="fiscal-years">
    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
        <h2 class="font-bold text-slate-800">Fiscal year setup</h2>
        <p class="mt-1 text-sm text-slate-500">The active year controls new budgets, activities, procurement, and payments across the organisation. Only administrators can activate a year or view historical years here. Staff always work in the active year.</p>
        <p class="mt-2 text-xs text-slate-500">Use your AD or BS fiscal-year label. Enter the actual start and end dates in AD (Gregorian). Imported labels need their dates completed; no dates have been guessed.</p>
    </div>
    <div class="space-y-4 p-5 sm:p-6">
        @foreach($fiscalYears as $year)
            @if(!$year->is_legacy)
            <div class="rounded-xl border border-slate-200 p-4">
                <form method="POST" action="{{ route('fiscal-year.select') }}" class="mb-2">@csrf<input type="hidden" name="fiscal_year_id" value="{{ $year->id }}"><button class="text-xs font-semibold text-teal-700">View this year</button></form>
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-800">{{ $year->name }} <span class="ml-2 text-xs {{ $year->id === $activeFiscalYearId ? 'text-teal-700' : 'text-gray-500' }}">{{ $year->id === $activeFiscalYearId ? 'Active for the organisation' : 'Read-only' }}</span></p>
                    @if($year->id !== $activeFiscalYearId)
                    <form method="POST" action="{{ route('admin.fiscal-years.activate', $year) }}">@csrf<button type="submit" class="text-sm font-semibold text-teal-700">Set as active year</button></form>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.fiscal-years.update', $year) }}" class="grid gap-3 sm:grid-cols-4 sm:items-end">
                    @csrf @method('PUT')
                    <label class="text-xs font-semibold text-slate-600">Fiscal year label<input name="name" value="{{ $year->name }}" required maxlength="30" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                    <label class="text-xs font-semibold text-slate-600">Start date (AD)<input type="date" name="starts_on" value="{{ $year->starts_on?->format('Y-m-d') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                    <label class="text-xs font-semibold text-slate-600">End date (AD)<input type="date" name="ends_on" value="{{ $year->ends_on?->format('Y-m-d') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Save year setup</button>
                </form>
            </div>
            @endif
        @endforeach
        <form method="POST" action="{{ route('admin.fiscal-years.store') }}" class="rounded-xl bg-slate-50 p-4">
            @csrf
            <h3 class="mb-3 text-sm font-bold text-slate-800">Add fiscal year</h3>
            <div class="grid gap-3 sm:grid-cols-4 sm:items-end">
                <label class="text-xs font-semibold text-slate-600">Fiscal year label<input name="name" value="{{ old('name') }}" required maxlength="30" placeholder="e.g. 2026/2027" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                <label class="text-xs font-semibold text-slate-600">Start date (AD)<input type="date" name="starts_on" value="{{ old('starts_on') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                <label class="text-xs font-semibold text-slate-600">End date (AD)<input type="date" name="ends_on" value="{{ old('ends_on') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                <button type="submit" class="rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white">Add fiscal year</button>
            </div>
        </form>
        <p class="text-xs text-slate-500">Changing the active year does not delete or move any entries. Shared organisation setup and user permissions apply across years. Unassigned historical entries remain available in the year selector.</p>
    </div>
</section>
