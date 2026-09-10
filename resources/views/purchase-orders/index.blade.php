@extends('layouts.app')
@section('title', 'Purchase Orders')
@section('page-title', 'Purchase Orders')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-3">
    @can('purchase_orders.create')<div class="flex justify-end gap-3"><a href="{{ route('purchase-orders.create') }}" class="btn-primary">+ New purchase order</a></div>@endcan

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-3 flex-1 flex-wrap items-end"><input type="hidden" name="status" value="{{ request('status') }}"><label class="block flex-1 min-w-[200px]"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find a purchase order</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="PO number" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label><button class="btn-primary h-[42px]">Apply filters</button></form>
    </div>

    {{-- ══ Status Tabs ══ --}}
    @php
        $tabs = [
            ''                 => 'All',
            'generated'        => 'Generated',
            'pending_verification' => 'Awaiting Verification',
            'pending_approval' => 'Awaiting Approval',
            'approved'         => 'Approved',
            'rejected'         => 'Returned / Rejected',
            'sent_to_vendor'   => 'Issued to Vendor',
            'goods_pending'    => 'Goods Pending',
            'partially_received' => 'Partially Received',
            'fully_received'   => 'Fully Received',
            'cancelled'        => 'Cancelled',
        ];
        $currentStatus = request('status', '');
    @endphp
    <div class="mt-4 -mx-1 overflow-x-auto border-t border-gray-100 px-1 pt-3"><div class="flex min-w-max gap-1.5">
        @foreach($tabs as $value => $label)
        @can('purchase_orders.view')
<a href="{{ route('purchase-orders.index', array_merge(request()->except('status','page'), $value ? ['status'=>$value] : [])) }}"
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
        <x-super-admin-bulk-delete module="purchase_orders" />
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @if(auth()->user()->isSuperAdmin())<th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all purchase orders" data-bulk-delete-toggle="purchase_orders"></th>@endif
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">PO Number</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Linked RFQ</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="relative px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $po)
                    @php
                        $statusColors = [
                            'generated'          => 'bg-blue-100 text-blue-700',
                            'pending_verification' => 'bg-amber-100 text-amber-700',
                            'pending_approval'   => 'bg-yellow-100 text-yellow-700',
                            'approved'           => 'bg-green-100 text-green-700',
                            'rejected'           => 'bg-red-100 text-red-700',
                            'draft'              => 'bg-gray-100 text-gray-600',
                            'sent_to_vendor'     => 'bg-indigo-100 text-indigo-700',
                            'goods_pending'      => 'bg-amber-100 text-amber-700',
                            'partially_received' => 'bg-orange-100 text-orange-700',
                            'fully_received'     => 'bg-emerald-100 text-emerald-700',
                            'completed'          => 'bg-green-100 text-green-700',
                            'cancelled'          => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        @if(auth()->user()->isSuperAdmin())<td class="px-3 py-3"><input type="checkbox" value="{{ $po->id }}" aria-label="Select {{ $po->po_number }}" data-bulk-delete-record="purchase_orders"></td>@endif
                        <td class="px-3 py-2 font-mono text-xs font-bold text-gray-700">{{ $po->po_number }}</td>
                        <td class="px-3 py-2 text-gray-800 font-medium">{{ $po->vendor?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500">
                            @if($po->rfqQuote?->rfq)
                                @can('rfq.view')
<a href="{{ route('rfq.show', $po->rfqQuote->rfq) }}" class="text-teal-600 hover:underline font-mono">
                                    {{ $po->rfqQuote->rfq->rfq_number }}
                                </a>
@endcan
                            @else
                                <span class="text-gray-400 italic">Standalone</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right font-mono text-gray-700">
                            Rs {{ number_format($po->total_amount, 2) }}
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$po->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $po->statusLabel() }}
                            </span>
                            @if($po->approvalChain)<p class="mt-1 max-w-40 truncate text-xs text-gray-400" title="{{ $po->approvalChain->name }}">{{ $po->approvalChain->name }}</p>@endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-400">{{ $po->created_at->format('d M Y') }}</td>
                        <td class="px-3 py-2 text-right space-x-2">
                            @can('purchase_orders.view')
<a href="{{ route('purchase-orders.show', $po) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View →</a>
@endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="px-5 py-12 text-center text-gray-400 text-sm">
                            No purchase orders yet. @can('purchase_orders.create')
<a href="{{ route('purchase-orders.create') }}" class="text-teal-600 hover:underline">Create one now</a>
@endcan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="px-3 py-2 border-t border-gray-100">
            {{ $orders->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
