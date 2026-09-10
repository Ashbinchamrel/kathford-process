@extends('layouts.app')
@section('title', 'Department Budgets')
@section('page-title', 'Department Budgets')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-3" x-data="{ showCreate: false, editId: null }">
    @can('budgets.manage')<div class="flex justify-end gap-3"><button @click="showCreate = !showCreate" class="btn-primary">+ Add budget activity</button></div>@endcan


    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div x-show="showCreate" x-cloak class="kcard p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Create a budget activity</h3>
        @can('budgets.manage')
<form method="POST" action="{{ route('budgets.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department <span class="text-red-500">*</span></label>
                <select name="department_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Select department…</option>
                    @foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') === $department->id)>{{ $department->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal year <span class="text-red-500">*</span></label>
                <input name="fiscal_year" value="{{ $workingFiscalYear?->name }}" readonly required class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title / Subject (Activity) <span class="text-red-500">*</span></label>
                <input name="activity_title" value="{{ old('activity_title') }}" required maxlength="200" placeholder="e.g. Annual IT Equipment Procurement" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Allocated budget (NPR) <span class="text-xs font-normal text-gray-400">optional</span></label>
                <input type="number" name="allocated_amount" value="{{ old('allocated_amount') }}" min="0" step="0.01" placeholder="Leave blank if not allocated" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-400">A Title / Subject can be used by Activity Forms without an allocation.</p>
            </div>
            <div class="flex items-end gap-3 pb-2">
                <input id="active" type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-teal-600">
                <label for="active" class="text-sm text-gray-700">Make available in Activity Forms</label>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Budget assumptions or internal reference…">{{ old('notes') }}</textarea>
            </div>
            <div class="md:col-span-2 flex gap-3">
                <button class="btn-primary">Save budget activity</button>
                <button type="button" @click="showCreate=false" class="btn-secondary">Cancel</button>
            </div>
        </form>
@endcan
    </div>

    <div class="kcard p-4 space-y-3">
        <form method="GET" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-4">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Find a budget activity</label>
                <div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="Activity or department" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm"></div>
            </div>
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
                <select name="department_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All departments</option>
                    @foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') === $department->id)>{{ $department->name }}</option>@endforeach
                </select>
            </div>
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Fiscal year</label>
                <input value="{{ $workingFiscalYear?->name }}" readonly aria-label="Selected fiscal year" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm"><p class="mt-1 text-xs text-gray-500">Change the year using the selector at the top of the page.</p>
            </div>
            <div class="lg:col-span-2"><button class="btn-primary w-full justify-center">Apply filters</button></div>
        </form>

        <details class="border-t border-gray-100 pt-3"><summary class="cursor-pointer text-sm font-semibold text-teal-700">Import budget activities</summary><div class="mt-3">
            @can('budgets.manage')
<form method="POST" action="{{ route('budgets.upload') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 lg:grid-cols-12 lg:items-end">
                @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
                <div class="lg:col-span-4">
                    <p class="text-sm font-semibold text-gray-800">Import budget activities</p>
                    <p class="mt-1 text-xs text-gray-500">Rows must use the selected active year. CSV headers: <code>department, fiscal_year, title</code>. Optional: <code>allocation, notes, active</code>.</p>
                </div>
                <div class="lg:col-span-6">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">CSV file</label>
                    <input type="file" name="budget_file" accept=".csv,text/csv" required class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100">
                </div>
                <div class="lg:col-span-2"><button class="btn-secondary w-full justify-center">Upload CSV</button></div>
            </form>
@endcan
        </div></details>
    </div>

    <div class="kcard overflow-hidden">
        <div class="overflow-x-auto">
            <table class="ktable min-w-[1120px] table-fixed">
                <colgroup>
                    <col class="w-[15%]"><col class="w-[12%]"><col class="w-[27%]"><col class="w-[12%]"><col class="w-[12%]"><col class="w-[14%]"><col class="w-[8%]"><col class="w-[72px]">
                </colgroup>
                <thead><tr><th class="px-5">Department</th><th class="px-5">Fiscal year</th><th class="px-5">Title / Subject</th><th class="px-5 text-right">Allocation</th><th class="px-5 text-right">Committed</th><th class="px-5 text-right">Available / variance</th><th class="px-5">Status</th><th class="px-5 text-right">Action</th></tr></thead>
                <tbody>
                @forelse($budgetRows as $row)
                    @php($budget = $row['budget'])
                    <tr>
                        <td class="px-5 font-medium break-words">{{ $budget->department?->name }}</td>
                        <td class="px-5 whitespace-nowrap">{{ $budget->fiscal_year }}</td>
                        <td class="px-5"><p class="font-medium break-words">{{ $budget->activity_title }}</p>@if($budget->notes)<p class="text-xs text-gray-400 mt-1 break-words">{{ $budget->notes }}</p>@endif</td>
                        <td class="px-5 text-right tabular-nums whitespace-nowrap">Rs {{ number_format($budget->allocated_amount, 2) }}</td>
                        <td class="px-5 text-right tabular-nums whitespace-nowrap">Rs {{ number_format($row['reserved'], 2) }}</td>
                        <td class="px-5 text-right tabular-nums">
                            @if($row['remaining'] >= 0)
                                <p class="font-semibold whitespace-nowrap text-green-700">Rs {{ number_format($row['remaining'], 2) }}</p><p class="mt-0.5 text-xs text-gray-400">Available</p>
                            @else
                                <p class="font-semibold whitespace-nowrap text-red-600">Over by Rs {{ number_format(abs($row['remaining']), 2) }}</p><p class="mt-0.5 text-xs text-red-500">Budget variance</p>
                            @endif
                        </td>
                        <td class="px-5"><span class="badge {{ $budget->is_active ? 'badge-approved' : 'badge-draft' }}">{{ $budget->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="px-5 text-right">@can('budgets.manage')<button @click="editId = editId === '{{ $budget->id }}' ? null : '{{ $budget->id }}'" class="text-teal-700 text-sm font-medium">Edit</button>@endcan</td>
                    </tr>
                    <tr x-show="editId === '{{ $budget->id }}'" x-cloak><td colspan="8" class="bg-gray-50">
                        @can('budgets.manage')
<form method="POST" action="{{ route('budgets.update', $budget) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 p-2">
                            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"> @method('PUT')
                            <select name="department_id" required class="rounded border border-gray-300 px-2 py-2 text-sm">@foreach($departments as $department)<option value="{{ $department->id }}" @selected($budget->department_id === $department->id)>{{ $department->name }}</option>@endforeach</select>
                            <input name="fiscal_year" value="{{ $workingFiscalYear?->name }}" readonly required class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">fiscal_year }}" required class="rounded border border-gray-300 px-2 py-2 text-sm">
                            <input name="activity_title" value="{{ $budget->activity_title }}" required class="rounded border border-gray-300 px-2 py-2 text-sm">
                            <input type="number" name="allocated_amount" value="{{ $budget->allocated_amount ?: '' }}" min="0" step="0.01" placeholder="Optional allocation" class="rounded border border-gray-300 px-2 py-2 text-sm">
                            <div class="flex items-center gap-3"><label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($budget->is_active)> Active</label><button class="btn-primary">Save</button></div>
                            <textarea name="notes" class="md:col-span-5 rounded border border-gray-300 px-2 py-2 text-sm" rows="2" placeholder="Notes">{{ $budget->notes }}</textarea>
                        </form>
@endcan
                    </td></tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-400 py-12">No budget activities found. Create one or upload a CSV to begin.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
