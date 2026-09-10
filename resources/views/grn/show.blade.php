@extends('layouts.app')
@section('title', $grn->grn_number)
@section('page-title', 'GRN: ' . $grn->grn_number)

@section('content')
<div class="max-w-4xl space-y-5">
    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-wrap gap-6 justify-between">
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Purchase Order</p>
                @can('purchase_orders.view')
<a href="{{ route('purchase-orders.show', $grn->purchaseOrder) }}" class="text-teal-600 hover:underline font-mono font-bold">{{ $grn->purchaseOrder?->po_number }}</a>
@endcan
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Vendor</p>
                <p class="font-medium text-gray-800">{{ $grn->purchaseOrder?->vendor?->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Date Received</p>
                <p class="font-medium text-gray-800">{{ $grn->received_date?->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Status</p>
                @if($grn->isConfirmed())
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">Confirmed</span>
                @else
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-700">Awaiting Confirmation</span>
                @endif
            </div>
        </div>
        <dl class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-0.5">Received By</dt>
                <dd class="text-gray-700">{{ $grn->receivedByUser?->name }}</dd>
            </div>
            @if($grn->notes)
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-0.5">Remarks</dt>
                <dd class="text-gray-700">{{ $grn->notes }}</dd>
            </div>
            @endif
            @if($grn->isConfirmed())
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-0.5">Confirmed By</dt>
                <dd class="text-gray-700">{{ $grn->confirmedBy?->name }} on {{ $grn->confirmed_at?->format('d M Y') }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">Items Received</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Item</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ordered</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Received</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Condition</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($grn->items as $item)
                    <tr>
                        <td class="px-5 py-3 text-gray-800">{{ $item->lineItem?->item_name }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $item->ordered_quantity }}</td>
                        <td class="px-5 py-3 text-right font-mono {{ $item->isFullyReceived() ? 'text-green-600 font-bold' : 'text-orange-600' }}">{{ $item->received_quantity }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $item->item_condition_note ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        @if(!$grn->isConfirmed() && auth()->user()->hasAnyRole(['finance','super_admin']))
        @can('grn.confirm')
<form method="POST" action="{{ route('grn.confirm', $grn) }}" onsubmit="return confirm('Confirm this GRN?')">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <button class="px-5 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold">Confirm GRN</button>
        </form>
@endcan
        @endif
        @can('grn.view')
<a href="{{ route('grn.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2">← Back</a>
@endcan
    </div>
</div>
@endsection
