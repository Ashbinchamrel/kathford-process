<?php /** @var \Illuminate\Pagination\LengthAwarePaginator $items */ ?>
@extends('layouts.app')
@section('title', 'Store Action')
@section('page-title', 'Store Action')

@section('content')
<div class="mx-auto max-w-[1200px] space-y-3">
    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="font-semibold text-gray-800">Items available in Store</h2>
        <p class="mt-1 text-sm text-gray-500">Items marked "Available in Store" during RFQ preparation. These skip vendor sourcing entirely — tick each one Issued once it's been handed out.</p>
    </div>

    @forelse($items as $item)
    @php($activity = $item->rfq?->activityForm)
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-bold text-gray-700">{{ $activity?->form_number ?? $item->rfq?->rfq_number }}</span>
                    @if($activity?->category)<span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-semibold text-gray-700">{{ $activity->category->name }}</span>@endif
                    @if($item->isStoreIssued())
                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Issued</span>
                    @else
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Awaiting issuance</span>
                    @endif
                </div>
                <p class="mt-1 text-sm font-semibold text-gray-800">{{ $activity?->activity_name ?? '—' }}</p>
            </div>
            @if($activity)
            @can('activity_forms.view')
            <a href="{{ route('activity-forms.show', $activity) }}" class="text-sm font-semibold text-teal-700 hover:text-teal-800">View Activity Form →</a>
            @endcan
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 px-4 py-4 sm:grid-cols-4">
            <div>
                <p class="text-xs font-semibold uppercase text-gray-400">Requested by</p>
                <p class="mt-1 text-sm text-gray-800">{{ $activity?->creator?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-400">Department</p>
                <p class="mt-1 text-sm text-gray-800">{{ $activity?->department?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-400">Needed by</p>
                <p class="mt-1 text-sm text-gray-800">{{ $activity?->deadline_date?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-400">RFQ reference</p>
                <p class="mt-1 font-mono text-sm text-gray-800">{{ $item->rfq?->rfq_number ?? '—' }}</p>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase text-gray-400">Item to issue</p>
                    <p class="mt-1 text-base font-semibold text-gray-900">{{ $item->description }}</p>
                    <p class="mt-0.5 text-sm text-gray-600">Quantity: <span class="font-mono font-semibold">{{ $item->quantity }} {{ $item->unit }}</span></p>
                    @if($item->request_remarks)
                    <p class="mt-2 text-sm text-gray-600"><span class="font-semibold text-gray-700">Specification / remarks:</span> {{ $item->request_remarks }}</p>
                    @endif
                    @if($activity?->remarks)
                    <p class="mt-1 text-sm text-gray-500"><span class="font-semibold text-gray-600">Activity remarks:</span> {{ $activity->remarks }}</p>
                    @endif
                </div>
                <div class="shrink-0 text-right">
                    @if($item->isStoreIssued())
                    <p class="text-sm font-medium text-green-700">✓ Issued</p>
                    <p class="text-xs text-gray-500">{{ $item->storeIssuedBy?->name }} · {{ $item->store_issued_at->format('d M Y, g:i A') }}</p>
                    @else
                    @can('checklists.complete')
                    <form method="POST" action="{{ route('store-action.issue', $item) }}">
                        @csrf
                        <input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
                        <button class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Mark Issued</button>
                    </form>
                    @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="rounded-xl border border-gray-200 bg-white px-5 py-14 text-center text-gray-400">No items are currently marked Available in Store.</div>
    @endforelse

    @if($items->hasPages())<div class="pt-2">{{ $items->links() }}</div>@endif
</div>
@endsection
