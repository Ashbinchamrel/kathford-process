@extends('layouts.app')
@section('title', $purchaseOrder->po_number)
@section('page-title', 'Purchase Order')

@section('content')
@php($po = $purchaseOrder)
<div class="max-w-4xl space-y-5">
    {{-- Header Banner --}}
    <div class="bg-[#0B1E3D] text-white rounded-xl p-6 flex flex-wrap justify-between gap-4">
        <div>
            <p class="text-teal-300 text-xs font-semibold uppercase tracking-wide mb-1">Purchase Order</p>
            <p class="font-bold text-2xl font-mono">{{ $po->po_number }}</p>
        </div>
        <div class="text-right">
            <p class="text-gray-400 text-xs mb-1">Grand Total</p>
            <p class="font-bold text-2xl">Rs {{ number_format($po->total_amount, 2) }}</p>
        </div>
    </div>

    {{-- Parties --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3">From (Buyer)</p>
            <p class="font-bold text-gray-800">{{ config('kathford.college_name') }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ config('kathford.college_address') }}</p>
            <p class="text-sm text-gray-500">{{ config('kathford.college_phone') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3">To (Vendor)</p>
            <p class="font-bold text-gray-800">{{ $po->vendor?->name }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $po->vendor?->address }}</p>
            <p class="text-sm text-gray-500">{{ $po->vendor?->email }}</p>
            <p class="text-sm text-gray-500">{{ $po->vendor?->phone }}</p>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Issue Date</dt>
                <dd class="text-gray-700">{{ $po->created_at?->format('d M Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Delivery By</dt>
                <dd class="text-gray-700">{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('d M Y') : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">RFQ source</dt>
                <dd class="text-gray-700">@if($po->rfqQuote?->rfq)@can('rfq.view')
<a class="text-teal-700 hover:underline" href="{{ route('rfq.show', $po->rfqQuote->rfq) }}">{{ $po->rfqQuote->rfq->rfq_number }}</a>
@endcan
@else Standalone PO @endif</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Status</dt>
                <dd><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-700">{{ $po->statusLabel() }}</span></dd>
            </div>
        </dl>
        @if($po->terms_and_conditions)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Terms & Conditions</p>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $po->terms_and_conditions }}</p>
        </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">Ordered Items</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Unit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Unit Rate</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($po->items as $idx => $item)
                    <tr>
                        <td class="px-5 py-3 text-gray-400">{{ $idx + 1 }}</td>
                        <td class="px-5 py-3 text-gray-800"><span class="block">{{ $item->description }}</span>@if($item->request_remarks)<span class="mt-1 block text-xs text-gray-500">{{ $item->request_remarks }}</span>@endif</td>
                        <td class="px-5 py-3 text-gray-500">{{ $item->unit }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $item->quantity }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rs {{ number_format($item->unit_rate, 2) }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rs {{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr><td colspan="5" class="px-5 py-2 text-right font-semibold text-gray-600">Total Amount</td><td class="px-5 py-2 text-right font-mono text-gray-800">Rs {{ number_format($po->subtotal, 2) }}</td></tr>
                    <tr><td colspan="5" class="px-5 py-2 text-right font-semibold text-gray-600">Tax Amount</td><td class="px-5 py-2 text-right font-mono text-gray-800">Rs {{ number_format($po->tax_amount, 2) }}</td></tr>
                    <tr class="bg-teal-50"><td colspan="5" class="px-5 py-3 text-right font-bold text-teal-900">Grand Total</td><td class="px-5 py-3 text-right font-mono font-bold text-teal-900">Rs {{ number_format($po->total_amount, 2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Vendor bills --}}
    @if($po->vendorBills->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-800">Vendor Bills</h2><p class="mt-1 text-sm text-gray-500">Submitted invoices/manual bills from {{ $po->vendor?->name }}.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bill</th><th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bill Date</th><th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bill Amount</th><th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Tax</th><th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">File</th></tr></thead>
                <tbody class="divide-y divide-gray-50">@foreach($po->vendorBills as $bill)<tr><td class="px-5 py-3"><p class="font-medium text-gray-800">{{ $bill->bill_number }}</p>@if($bill->notes)<p class="mt-1 max-w-md text-xs text-gray-500">{{ $bill->notes }}</p>@endif</td><td class="px-5 py-3 text-gray-600">{{ $bill->bill_date?->format('d M Y') }}</td><td class="px-5 py-3 text-right font-mono text-gray-800">Rs {{ number_format($bill->amount, 2) }}</td><td class="px-5 py-3 text-right font-mono text-gray-800">Rs {{ number_format($bill->tax_amount, 2) }}</td><td class="px-5 py-3"><a href="{{ route('purchase-orders.vendor-bills.download', [$po, $bill]) }}" class="font-semibold text-teal-700 hover:text-teal-800">Download bill →</a><p class="mt-1 text-xs text-gray-400">{{ $bill->original_name }}</p></td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
    @endif

    @if($po->procurementChecklists->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-1">Bill Control Checklists</h2><p class="mb-4 text-sm text-gray-500">Vendor bills must pass the operational checklist before they are sent to Accounts.</p>
        <div class="space-y-2">@foreach($po->procurementChecklists as $checklist)@can('checklists.view')
<a href="{{ route('checklists.show', $checklist) }}" class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 hover:bg-gray-100"><span><span class="font-medium text-gray-800">{{ $checklist->vendorBill?->bill_number }}</span><span class="ml-2 text-xs text-gray-500">{{ ucfirst($checklist->fulfillment_type) }}</span></span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $checklist->status === 'sent_to_accounts' ? 'bg-green-100 text-green-700' : ($checklist->status === 'ready_for_accounts' ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700') }}">{{ $checklist->statusLabel() }}</span></a>
@endcan
@endforeach</div>
    </div>
    @endif

    {{-- Approval workflow --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold text-gray-800">Approval Workflow</h2><p class="mt-1 text-sm text-gray-500">{{ $po->approvalChain ? 'Chain: '.$po->approvalChain->name : 'The administrator-configured Purchase Order chain will be assigned when this PO is submitted.' }}</p>@if($po->approvalChain && ($step = app(\App\Services\ApprovalService::class)->pendingStepLabel($po)))<p class="mt-2 text-xs font-semibold text-amber-800">Pending: {{ $step }}</p>@endif</div><span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ in_array($po->status, ['approved', 'sent_to_vendor']) ? 'bg-green-100 text-green-700' : (in_array($po->status, ['rejected']) ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $po->statusLabel() }}</span></div>
        @if($po->approvalActions->isNotEmpty())<div class="mt-4 space-y-2 border-t border-gray-100 pt-4">@foreach($po->approvalActions as $action)<div class="flex flex-wrap justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm"><span><strong>{{ $action->actor?->name }}</strong> · {{ ucfirst(str_replace('_', ' ', $action->decision)) }} <span class="text-xs text-gray-400">({{ $action->acted_at?->format('d M Y H:i') }})</span>@if($action->note)<span class="block mt-1 text-xs text-gray-500">{{ $action->note }}</span>@endif</span><span class="text-xs font-semibold text-gray-500">{{ $action->layerLabel() }}</span></div>@endforeach</div>@endif
        @if($po->isPendingVerification())
            @can('verify', $po)
            @can('purchase_orders.verify')
<form method="POST" action="{{ route('purchase-orders.verify', $po) }}" class="mt-5 border-t border-gray-100 pt-5">
                @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
                <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-800">1</span><div><p class="text-sm font-semibold text-gray-900">Verification required</p><p class="text-xs text-gray-500">Confirm the order or return it with a comment.</p></div></div>
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)_auto]">
                    <select name="decision" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="approved">Verify and forward</option><option value="modified_approved">Return for modification</option><option value="rejected">Reject</option></select>
                    <input name="note" placeholder="Add a comment (required when returning or rejecting)" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                    <button class="btn-primary whitespace-nowrap">Record verification</button>
                </div>
            </form>
@endcan
            @endcan
        @elseif($po->isPendingApproval())
            @can('finalApprove', $po)
            @can('purchase_orders.approve')
<form method="POST" action="{{ route('purchase-orders.approve', $po) }}" class="mt-5 border-t border-gray-100 pt-5">
                @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
                <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800">2</span><div><p class="text-sm font-semibold text-gray-900">Final approval required</p><p class="text-xs text-gray-500">Approve the order or return it with a comment.</p></div></div>
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)_auto]">
                    <select name="decision" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="approved">Approve purchase order</option><option value="modified_approved">Return for modification</option><option value="rejected">Reject</option></select>
                    <input name="note" placeholder="Add a comment (required when returning or rejecting)" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                    <button class="btn-primary whitespace-nowrap">Record approval</button>
                </div>
            </form>
@endcan
            @endcan
        @endif
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">

                @if($purchaseOrder->isEditable())
                @can('purchase_orders.edit')
<a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn-secondary">Edit</a>
@endcan
                @can('purchase_orders.delete')
<form method="POST" action="{{ route('purchase-orders.destroy', $purchaseOrder) }}" onsubmit="return confirm('Delete this Purchase Order?')" class="inline">
                    @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"> @method('DELETE')
                    <button class="btn-danger">Delete</button>
                </form>
@endcan
                @endif
        @can('purchase_orders.view')
<a href="{{ route('purchase-orders.pdf', $po) }}" target="_blank" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Download PDF
        </a>
@endcan
        @if($po->isEditable())
        @can('purchase_orders.submit')
<form method="POST" action="{{ route('purchase-orders.submit', $po) }}">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <button class="btn-primary">Submit for Approval</button>
        </form>
@endcan
        @endif
        @if($po->status === 'approved')
        @can('purchase_orders.send')
<form method="POST" action="{{ route('purchase-orders.send', $po) }}">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <button class="btn-primary">Send approved PO to vendor</button>
        </form>
@endcan
        @endif
        @if($po->procurementChecklists->isNotEmpty())
        @can('checklists.view')
<a href="{{ route('checklists.show', $po->procurementChecklists->first()) }}" class="btn-secondary">Open checklist</a>
@endcan
        @endif
        @can('purchase_orders.view')
<a href="{{ route('purchase-orders.index') }}" class="btn-quiet">← Back</a>
@endcan
    </div>
</div>
@endsection
