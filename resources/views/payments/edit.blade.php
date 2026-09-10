@extends('layouts.app')
@php
    $isProcessPayment = $payment->source !== 'manual' || $payment->vendor_bill_id;
@endphp
@section('title', 'Edit Payment Schedule')
@section('page-title', 'Edit Payment Schedule')

@section('content')
<div class="max-w-xl">@can('payments.edit')
<form method="POST" action="{{ route('payments.update', $payment) }}">@csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"> @method('PUT')
    <div class="mb-4 space-y-5 rounded-xl border border-gray-200 bg-white p-6">
        @if($isProcessPayment)
            <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm"><p class="font-bold text-blue-800">{{ $payment->purchaseOrder?->po_number }} · {{ $payment->vendor?->name }}</p><p class="mt-1 text-xs text-blue-700">Bill and activity information are retained from the completed procurement process.</p></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Payment Type <span class="text-red-500">*</span></label><select name="payment_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="full" @selected(old('payment_type', $payment->payment_type ?: 'full') === 'full')>Full payment</option><option value="partial" @selected(old('payment_type', $payment->payment_type) === 'partial')>Partial payment</option></select></div>
        @else
            <input type="hidden" name="payment_type" value="full">
        @endif
        <div><label class="mb-1 block text-sm font-medium text-gray-700">Account Name <span class="text-red-500">*</span></label><select name="payment_account_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('payment_account_id', $payment->payment_account_id) === $account->id)>{{ $account->name }}{{ $account->code ? ' · '.$account->code : '' }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-sm font-medium text-gray-700">Payment Amount (Rs) <span class="text-red-500">*</span></label><input type="number" name="amount_due" value="{{ old('amount_due', $payment->amount_due) }}" min="0.01" step="0.01" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono"></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-medium text-gray-700">Scheduled Month <span class="text-red-500">*</span></label><input type="month" name="schedule_month" value="{{ old('schedule_month', $payment->schedule_month?->format('Y-m') ?: $payment->scheduled_date?->format('Y-m')) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-sm font-medium text-gray-700">Scheduled Week <span class="text-red-500">*</span></label><select name="schedule_week" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach(range(1, 5) as $week)<option value="{{ $week }}" @selected((int) old('schedule_week', $payment->schedule_week ?: 1) === $week)>Week {{ $week }}</option>@endforeach</select></div></div>
        <div class="rounded-lg bg-amber-50 p-3"><label class="flex items-center gap-2 text-sm font-medium text-amber-900"><input type="hidden" name="tds_applied" value="0"><input type="checkbox" name="tds_applied" value="1" @checked(old('tds_applied', $payment->tds_applied)) class="rounded border-amber-300 text-amber-600"> Apply TDS deduction</label><label class="mt-2 block text-sm text-amber-900">TDS rate (%) <input type="number" name="tds_rate" value="{{ old('tds_rate', $payment->tds_rate) }}" min="0" max="100" step="0.01" class="ml-2 w-24 rounded border border-amber-200 bg-white px-2 py-1 text-right"></label></div>
        <div><label class="mb-1 block text-sm font-medium text-gray-700">Notes</label><textarea name="notes" rows="2" class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('notes', $payment->notes) }}</textarea></div>
    </div><div class="flex flex-wrap items-center gap-2"><button class="btn-primary">Save schedule</button>@can('payments.view')
<a href="{{ route('payments.show', $payment) }}" class="btn-quiet">Cancel</a>
@endcan</div>
</form>
@endcan</div>
@endsection
