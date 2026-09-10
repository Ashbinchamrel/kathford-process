@extends('layouts.app')
@section('title', 'Payment Details')
@section('page-title', 'Payment Details')

@section('content')
<div class="max-w-2xl space-y-5">
    @php
        $hasScheduleRows = ! $payment->parent_payment_id && $payment->schedules->isNotEmpty();
    @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Purchase Order</dt>
                <dd>@if($payment->purchaseOrder)@can('purchase_orders.view')
<a href="{{ route('purchase-orders.show', $payment->purchaseOrder) }}" class="text-teal-600 hover:underline font-mono font-bold">{{ $payment->purchaseOrder->po_number }}</a>
@endcan
@elseif($payment->source === 'direct_activity_form')<span class="text-gray-700">Activity Form · {{ $payment->activity_reference }}</span>@else<span class="text-gray-700">External / manual payment</span>@endif</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Vendor</dt>
                <dd class="text-gray-700">{{ $payment->vendor?->name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Amount</dt>
                <dd class="font-mono font-bold text-gray-800 text-lg">Rs {{ number_format($payment->amount_due, 2) }}</dd>
            </div>
            @if($payment->tds_applied)
            <div><dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Net payable</dt><dd class="font-mono font-bold text-teal-800 text-lg">Rs {{ number_format($payment->net_amount, 2) }}</dd></div>
            @endif
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Status</dt>
                <dd>
                    @if($payment->status === 'paid')
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">Paid</span>
                    @elseif($payment->status === 'pending_finance')
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">Awaiting Finance</span>
                    @elseif($payment->status === 'in_authorisation')
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-700">In Authorisation</span>
                    @elseif($payment->status === 'authorised')
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-teal-100 text-teal-700">Authorised for Payment</span>
                    @else
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">Scheduled</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Scheduled Period</dt>
                <dd class="text-gray-700">{{ $payment->status === 'pending_finance' ? 'To be scheduled by Finance' : ($payment->schedule_month?->format('F Y').' · Week '.$payment->schedule_week) }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Payment Type</dt>
                <dd class="text-gray-700">{{ ucfirst($payment->payment_type ?: 'full') }} payment</dd>
            </div>
            @if($payment->account_name)
            <div><dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Account Name</dt><dd class="text-gray-700">{{ $payment->account_name }}</dd></div>
            @endif
            @if($payment->sub_account)
            <div><dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Sub Account</dt><dd class="text-gray-700">{{ $payment->sub_account }}</dd></div>
            @endif
            @if($payment->status === 'paid')
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Actual Payment Date</dt>
                <dd class="text-gray-700">{{ $payment->actual_date?->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Transaction Reference</dt>
                <dd class="font-mono text-gray-700">{{ $payment->payment_reference ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Paid By</dt>
                <dd class="text-gray-700">{{ $payment->markedPaidBy?->name }}</dd>
            </div>
            @endif
            @if($payment->bank_account_number)
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Bank Details (at time of scheduling)</dt>
                <dd class="font-mono text-sm bg-gray-50 rounded px-3 py-2">{{ $payment->bank_name ?: 'Bank' }} · {{ $payment->bank_account_number }}</dd>
            </div>
            @endif
            @if($payment->notes)
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Notes</dt>
                <dd class="text-gray-700">{{ $payment->notes }}</dd>
            </div>
            @endif
        </dl>
    </div>

    @if($hasScheduleRows)
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4"><div><h2 class="font-semibold text-gray-900">Payment schedule</h2><p class="mt-1 text-sm text-gray-500">Scheduled payments must equal the net payable amount.</p></div><p class="font-mono text-sm font-semibold text-teal-800">Scheduled Rs {{ number_format($payment->schedules->where('status', '!=', 'cancelled')->sum('net_amount'), 2) }}</p></div>
        <table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Scheduled period</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Account name</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Payment amount</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($payment->schedules->where('status', '!=', 'cancelled') as $schedule)<tr><td class="px-5 py-3">{{ $schedule->schedule_month?->format('M Y') }} · Week {{ $schedule->schedule_week }}</td><td class="px-5 py-3 text-gray-600">{{ $schedule->paymentAccount?->name ?: $schedule->account_name }}</td><td class="px-5 py-3 text-right font-mono font-semibold">Rs {{ number_format($schedule->net_amount, 2) }}</td><td class="px-5 py-3 text-gray-600">{{ ucfirst(str_replace('_', ' ', $schedule->status)) }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No payment periods have been scheduled yet.</td></tr>@endforelse</tbody></table>
    </div>
    @endif

    <div class="flex flex-wrap gap-3">
        @if(in_array($payment->status, ['pending_finance', 'scheduled']))
            @can('payments.edit')
<a href="{{ route('payments.edit', $payment) }}" class="btn-secondary">{{ $hasScheduleRows ? 'Process payment' : ($payment->source === 'imported_vendor' ? 'Schedule payment' : 'Edit') }}</a>
@endcan
        @endif
        @can('payments.mark_paid')
            @if($payment->status === 'authorised')
                <button onclick="document.getElementById('mark-paid-modal').classList.remove('hidden')" class="px-5 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold">Mark as Paid</button>
            @endif
        @endcan
        @can('payments.view')
<a href="{{ route('payments.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2">← Back</a>
@endcan
    </div>
</div>

{{-- Mark Paid Modal --}}
@can('payments.mark_paid')
<div id="mark-paid-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-gray-900 mb-4">Mark Payment as Paid</h3>
        @can('payments.mark_paid')
<form method="POST" action="{{ route('payments.mark-paid', $payment) }}">
            @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Actual Payment Date <span class="text-red-500">*</span></label>
                    <input type="date" name="actual_date" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Reference</label>
                    <input type="text" name="transaction_ref" placeholder="Bank ref / cheque number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button type="submit" class="flex-1 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold">Confirm Payment</button>
                <button type="button" onclick="document.getElementById('mark-paid-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm">Cancel</button>
            </div>
        </form>
@endcan
    </div>
</div>
@if(request()->boolean('settle') && $payment->status === 'authorised')
<script>document.addEventListener('DOMContentLoaded', () => document.getElementById('mark-paid-modal').classList.remove('hidden'));</script>
@endif
@endcan
@endsection
