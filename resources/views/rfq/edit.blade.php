@extends('layouts.app')
@section('title', 'Edit RFQ')
@section('page-title', 'Edit RFQ')

@section('content')
<div class="max-w-3xl space-y-4">

    @if ($errors->any())
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
        Editing <span class="font-mono font-semibold">{{ $rfq->rfq_number }}</span> — only title, deadline and notes can be changed on an existing RFQ.
    </div>

    @can('rfq.edit')
<form method="POST" action="{{ route('rfq.update', $rfq) }}">
        @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
        @method('PUT')

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <select name="budget_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select a budget activity…</option>
                    @foreach($budgets as $budget)<option value="{{ $budget->id }}" @selected(old('budget_id', $rfq->budget_id ?: $rfq->activityForm?->budget_id) === $budget->id)>{{ $budget->activity_title }} · {{ $budget->fiscal_year }}</option>@endforeach
                </select>
                @error('budget_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quotation Deadline</label>
                <input type="date" name="deadline" value="{{ old('deadline', $rfq->deadline?->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Instructions</label>
                <textarea name="notes" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none">{{ old('notes', $rfq->notes) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3 mt-4">
            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white" style="background:#0B1E3D;">
                Save Changes
            </button>
            @can('rfq.view')
<a href="{{ route('rfq.show', $rfq) }}" class="text-gray-500 hover:text-gray-700 text-sm py-2.5">Cancel</a>
@endcan
        </div>
    </form>
@endcan
</div>
@endsection
