@extends('layouts.app')
@section('title', 'Goods Received Notes')
@section('page-title', 'Goods Received Notes')

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <x-super-admin-bulk-delete module="grns" />
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @if(auth()->user()->isSuperAdmin())<th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all GRNs" data-bulk-delete-toggle="grns"></th>@endif
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">GRN Number</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">PO</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Received By</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($grns as $grn)
                    <tr class="hover:bg-gray-50">
                        @if(auth()->user()->isSuperAdmin())<td class="px-3 py-3"><input type="checkbox" value="{{ $grn->id }}" aria-label="Select {{ $grn->grn_number }}" data-bulk-delete-record="grns"></td>@endif
                        <td class="px-5 py-3 font-mono text-xs font-bold text-gray-700">{{ $grn->grn_number }}</td>
                        <td class="px-5 py-3">
                            @can('purchase_orders.view')
<a href="{{ route('purchase-orders.show', $grn->purchaseOrder) }}" class="text-teal-600 hover:underline text-xs">{{ $grn->purchaseOrder?->po_number }}</a>
@endcan
                        </td>
                        <td class="px-5 py-3 text-gray-700">{{ $grn->purchaseOrder?->vendor?->name }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $grn->receivedByUser?->name }}</td>
                        <td class="px-5 py-3 text-xs text-gray-400">{{ $grn->received_date?->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            @if($grn->isConfirmed())
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Confirmed</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending Confirmation</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            @can('grn.view')
<a href="{{ route('grn.show', $grn) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View</a>
@endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="px-5 py-12 text-center text-gray-400">No GRNs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($grns->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $grns->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
