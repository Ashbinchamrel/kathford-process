@extends('layouts.app')
@section('title', 'Purchase Requests')
@section('page-title', 'Purchase Requests')

@section('content')
<div class="space-y-4">

    {{-- ══ Toolbar ══ --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by PR number or title…"
                   class="flex-1 min-w-[200px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            <button type="submit" class="btn-primary">Search</button>
        </form>
        <a href="{{ route('purchase-requests.create') }}"
           class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold text-white whitespace-nowrap"
           style="background:#0B1E3D;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New PR
        </a>
    </div>

    {{-- ══ Status Tab Pills ══ --}}
    @php
        $tabs = [
            ''                    => 'All',
            'draft'               => 'Draft',
            'pending_verification'=> 'Awaiting Verification',
            'pending_approval'    => 'Awaiting Approval',
            'approved'            => 'Approved',
            'rejected'            => 'Rejected',
            'in_rfq'              => 'In RFQ',
            'ordered'             => 'Ordered',
            'received'            => 'Received',
            'closed'              => 'Closed',
        ];
        $currentStatus = request('status', '');
    @endphp
    <div class="flex flex-wrap gap-2">
        @foreach($tabs as $value => $label)
        <a href="{{ route('purchase-requests.index', array_merge(request()->except('status','page'), $value ? ['status'=>$value] : [])) }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors
               {{ $currentStatus === $value
                   ? 'border-transparent text-white'
                   : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}"
           @if($currentStatus === $value) style="background:#0B1E3D" @endif>
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- ══ Flash Messages ══ --}}
    @if(session('success'))
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- ══ Table ══ --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">PR Number</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Linked Activity</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Requested By</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Est. Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $pr)
                    @php
                        $approvalService = app(\App\Services\ApprovalService::class);
                        $needsAction = $approvalService->canVerify($pr, auth()->user()) || $approvalService->canApprove($pr, auth()->user());
                        $statusColors = [
                            'draft'               => 'bg-gray-100 text-gray-600',
                            'pending_verification'=> 'bg-blue-100 text-blue-700',
                            'verified'            => 'bg-teal-100 text-teal-700',
                            'pending_approval'    => 'bg-amber-100 text-amber-700',
                            'approved'            => 'bg-green-100 text-green-700',
                            'rejected'            => 'bg-red-100 text-red-700',
                            'in_rfq'              => 'bg-purple-100 text-purple-700',
                            'ordered'             => 'bg-indigo-100 text-indigo-700',
                            'received'            => 'bg-emerald-100 text-emerald-700',
                            'closed'              => 'bg-gray-100 text-gray-500',
                        ];
                        $statusLabels = [
                            'pending_verification' => 'Awaiting Verification',
                            'pending_approval'     => 'Awaiting Approval',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $needsAction ? 'bg-amber-50 hover:bg-amber-100' : '' }}">
                        <td class="px-5 py-3 font-mono text-xs font-bold text-gray-700">
                            {{ $pr->form_number }}
                            @if($needsAction)
                                <span class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-400"></span>
                            @endif
                        </td>
                        <td class="px-5 py-3 font-medium text-gray-900 max-w-xs truncate">{{ $pr->title }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs">
                            @if($pr->activityForm)
                                @can('activity_forms.view')
<a href="{{ route('activity-forms.show', $pr->activityForm) }}" class="text-teal-600 hover:underline font-mono">{{ $pr->activityForm->form_number }}</a>
@endcan
                            @else —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $pr->creator?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-gray-700">
                            Rs {{ number_format($pr->total_amount, 2) }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$pr->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $statusLabels[$pr->status] ?? ucfirst(str_replace('_', ' ', $pr->status)) }}
                            </span>
                            @if($pr->approvalChain)<p class="mt-1 max-w-40 truncate text-xs text-gray-400" title="{{ $pr->approvalChain->name }}">{{ $pr->approvalChain->name }}</p>@endif
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-400">{{ $pr->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('purchase-requests.show', $pr) }}"
                               class="text-sm font-medium {{ $needsAction ? 'text-amber-700 hover:text-amber-900' : 'text-teal-600 hover:text-teal-700' }}">
                                {{ $needsAction ? 'Act Now →' : 'View →' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-400 text-sm">
                            @if(request('status'))
                                No purchase requests with this status.
                            @else
                                No purchase requests yet. <a href="{{ route('purchase-requests.create') }}" class="text-teal-600 hover:underline">Create one now</a>.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $requests->withQueryString()->links() }}
        </div>
        @endif
    </div>

    {{-- ══ Legend ══ --}}
    <div class="flex items-center gap-2 text-xs text-gray-400">
        <span class="inline-block w-2 h-2 rounded-full bg-amber-400"></span>
        <span>Rows highlighted in amber require your action</span>
    </div>
</div>
@endsection
