@extends('layouts.app')
@section('title', 'RFQ / Quotations')
@section('page-title', 'RFQs & Quotations')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-3">
    @can('rfq.create')<div class="flex justify-end gap-3"><a href="{{ route('rfq.create') }}" class="btn-primary">+ New RFQ</a></div>@endcan

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-3 flex-1 flex-wrap items-end"><input type="hidden" name="status" value="{{ request('status') }}"><label class="block flex-1 min-w-[200px]"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find an RFQ</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="RFQ number or title" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label><button class="btn-primary h-[42px]">Apply filters</button></form>
    </div>

    {{-- ══ Status Tabs ══ --}}
    @php
        $tabs = [
            ''          => 'All',
            'draft'     => 'Draft',
            'sent'      => 'Invitations Sent',
            'quotes_received' => 'Quotes Received',
            'partially_awarded' => 'Partially Awarded',
            'items_awarded' => 'All Items Awarded',
            'closed'    => 'Closed',
            'cancelled' => 'Cancelled',
        ];
        $currentStatus = request('status', '');
    @endphp
    <div class="mt-4 -mx-1 overflow-x-auto border-t border-gray-100 px-1 pt-3"><div class="flex min-w-max gap-1.5">
        @foreach($tabs as $value => $label)
        @can('rfq.view')
<a href="{{ route('rfq.index', array_merge(request()->except('status','page'), $value ? ['status'=>$value] : [])) }}"
           class="rounded-lg px-3 py-2 text-xs font-semibold transition
               {{ $currentStatus === $value ? 'bg-[#0B1E3D] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}"
           @if($currentStatus === $value) style="background:#0B1E3D" @endif>
            {{ $label }}
        </a>
@endcan
        @endforeach
    </div></div>
    </section>

    {{-- ══ Flash ══ --}}
    @if(session('success'))
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- ══ Table ══ --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <x-super-admin-bulk-delete module="rfqs" />
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @if(auth()->user()->isSuperAdmin())<th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all RFQs" data-bulk-delete-toggle="rfqs"></th>@endif
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">RFQ Number</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Activity Form</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendors</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Deadline</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                        <th class="relative px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rfqs as $rfq)
                    @php
                        $statusColors = [
                            'draft'     => 'bg-gray-100 text-gray-600',
                            'open'      => 'bg-blue-100 text-blue-700',
                            'sent'      => 'bg-blue-100 text-blue-700',
                            'quotes_received' => 'bg-indigo-100 text-indigo-700',
                            'partially_awarded' => 'bg-amber-100 text-amber-700',
                            'items_awarded' => 'bg-green-100 text-green-700',
                            'closed'    => 'bg-green-100 text-green-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                        $submitted = $rfq->quotes->where('status', 'submitted')->count();
                        $total     = $rfq->quotes->count();
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        @if(auth()->user()->isSuperAdmin())<td class="px-3 py-3"><input type="checkbox" value="{{ $rfq->id }}" aria-label="Select {{ $rfq->rfq_number }}" data-bulk-delete-record="rfqs"></td>@endif
                        <td class="px-3 py-2 font-mono text-xs font-bold text-gray-700">{{ $rfq->rfq_number }}</td>
                        <td class="px-3 py-2 font-medium text-gray-900 max-w-xs truncate">
                            {{ $rfq->title ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500">
                            @if($rfq->activityForm)
                                @can('activity_forms.view')
<a href="{{ route('activity-forms.show', $rfq->activityForm) }}" class="text-teal-600 hover:underline font-mono">
                                    {{ $rfq->activityForm->form_number }}
                                </a>
@endcan
                            @else
                                <span class="text-gray-400 italic">Standalone</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600">
                            {{ $total }} vendor{{ $total !== 1 ? 's' : '' }}
                            @if($submitted > 0)
                                <span class="ml-1 text-teal-600 font-semibold">({{ $submitted }} quoted)</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-400">
                            @if($rfq->deadline)
                                <span class="{{ $rfq->deadline->isPast() && $rfq->status !== 'closed' ? 'text-red-500 font-semibold' : '' }}">
                                    {{ $rfq->deadline->format('d M Y') }}
                                </span>
                            @else —
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$rfq->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $rfq->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $rfq->createdBy?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            @can('rfq.view')
<a href="{{ route('rfq.show', $rfq) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View →</a>
@endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 9 : 8 }}" class="px-5 py-12 text-center text-gray-400 text-sm">
                            No RFQs found. @can('rfq.create')
<a href="{{ route('rfq.create') }}" class="text-teal-600 hover:underline">Create one now</a>
@endcan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rfqs->hasPages())
        <div class="px-3 py-2 border-t border-gray-100">
            {{ $rfqs->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
