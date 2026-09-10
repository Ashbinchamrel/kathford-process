@extends('layouts.app')

@section('title', 'Process Payment')
@section('page-title', 'Process Payment')

@section('content')
@php
    $isChecklistPayment = $payment->source === 'checklist';
    $isExternalPayment = $payment->source === 'external';
    $isImportedInvoice = $payment->source === 'imported_vendor';
    $isDirectActivityPayment = $payment->source === 'direct_activity_form';
    $paymentLabel = $isChecklistPayment ? 'Checklist payment' : ($isImportedInvoice ? 'Imported vendor invoice' : ($isDirectActivityPayment ? 'Approved activity form' : 'External payment'));
    $paymentReference = $isChecklistPayment ? ($payment->purchaseOrder?->po_number ?: 'Checklist payment') : ($isImportedInvoice ? (($payment->bill_number ?: 'Imported invoice').' · '.($payment->vendor?->name ?: 'Vendor')) : ($isDirectActivityPayment ? ($payment->activity_reference ?: 'Approved activity form') : ($payment->vendor?->name ?: $payment->payee?->name ?: 'External payment')));
    $paymentSubline = $isChecklistPayment
        ? 'Bill '.($payment->bill_number ?: '—').' · '.($payment->vendor?->name ?: 'No vendor')
        : ($isImportedInvoice ? 'Invoice details are retained from the import. Choose the account, TDS, and exact payment periods below.' : ($isDirectActivityPayment ? 'Activity details were carried forward. Complete the recipient, amount, account, TDS, and payment periods below.' : 'Gross amount is retained here; only the dated schedule rows are sent for authorisation.'));
    $existingSchedules = $payment->schedules->where('status', '!=', 'cancelled')->values();
    $lockedSchedules = $existingSchedules->filter(fn ($row) => $row->status !== 'scheduled' || $row->payment_authorisation_id);
    $lockedAmount = (float) $lockedSchedules->sum('net_amount');
    $existingSchedules = $existingSchedules->filter(fn ($row) => $row->status === 'scheduled' && !$row->payment_authorisation_id)->values();
    $existingType = old('payment_type', $payment->payment_type ?: 'full');
    $existingTds = old('tds_rate', $payment->tds_rate ?: 0);
    $scheduleRows = $existingSchedules->map(fn ($schedule) => ['month' => $schedule->schedule_month?->format('Y-m'), 'week' => (int) $schedule->schedule_week, 'amount' => (float) $schedule->net_amount, 'channel' => $schedule->payment_authorisation_channel_id])->all();
    if (is_array(old('schedule_month'))) {
        $scheduleRows = collect(old('schedule_month'))->map(fn ($month, $index) => ['month'=>$month, 'week'=>(int)old('schedule_week.'.$index, 1), 'amount'=>(float)old('schedule_amount.'.$index, 0), 'channel'=>old('schedule_channel_id.'.$index)])->values()->all();
    }
@endphp
<div class="max-w-5xl space-y-5" x-data="paymentSchedule({{ (float) $payment->amount_due }}, {{ (float) $existingTds }}, {{ old('tds_applied', $payment->tds_applied) ? 'true' : 'false' }}, @js($existingType), @js($scheduleRows), @js($channels->first()?->id))">
    @if($lockedSchedules->isNotEmpty())
    <section class="rounded-lg border border-gray-200 bg-white p-4 text-sm"><h2 class="font-semibold">Paid / authorised instalments — read-only</h2>
    @foreach($lockedSchedules as $lockedRow)<p class="mt-2">{{ $lockedRow->schedule_month?->format('M Y') }} · Week {{ $lockedRow->schedule_week }} · Rs {{ number_format($lockedRow->net_amount,2) }} · {{ ucfirst(str_replace('_',' ',$lockedRow->status)) }}</p>@endforeach
    <p class="mt-2 text-gray-500">Edit only the remaining periods below. Keep the amount, TDS, account and recipient unchanged.</p></section>
    @endif
    <section class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">{{ $paymentLabel }}</p>
        <div class="mt-1 flex flex-wrap items-center justify-between gap-3"><div><h1 class="font-mono text-xl font-bold text-blue-900">{{ $paymentReference }}</h1><p class="mt-1 text-sm text-blue-800">{{ $paymentSubline }}</p></div><p class="text-xs text-blue-700">Activity: {{ $payment->activity_name ?: $payment->activity_reference ?: 'Not linked' }}</p></div>
    </section>

    @can('payments.edit')
<form method="POST" action="{{ route('payments.update', $payment) }}" @submit="if (!isReady) { $event.preventDefault(); message = 'Schedule rows must exactly equal the net payable amount.'; }">
        @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"> @method('PUT')
        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payable amount</p>
                    @if($isDirectActivityPayment)
                        <label class="sr-only" for="amount_due">Payable amount</label><input id="amount_due" type="number" name="amount_due" x-model.number="gross" min="0.01" step="0.01" required class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right font-mono text-lg font-bold text-slate-900">
                        <p class="mt-1 text-xs text-slate-500">Update the estimated amount if the activity form did not include the final payable value.</p>
                    @else
                        <p class="mt-2 font-mono text-xl font-bold text-slate-900">Rs {{ number_format($payment->amount_due, 2) }}</p><p class="mt-1 text-xs text-slate-500">{{ $isChecklistPayment ? 'Locked from the Checklist bill' : 'Gross amount retained for this payment' }}</p>
                    @endif
                </div>
                <div class="rounded-lg bg-amber-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">TDS deduction</p><p class="mt-2 font-mono text-xl font-bold text-amber-900">Rs <span x-text="format(tdsAmount)"></span></p><p class="mt-1 text-xs text-amber-700"><span x-text="tdsApplied ? rate : 0"></span>% of payable amount</p></div>
                <div class="rounded-lg bg-teal-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Remaining to schedule</p><p class="mt-2 font-mono text-xl font-bold text-teal-900">Rs <span x-text="format(netPayable)"></span></p><p class="mt-1 text-xs text-teal-700">This exact amount must be scheduled</p></div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                @if($isDirectActivityPayment)
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Vendor <span class="font-normal text-gray-400">(optional)</span></label><select name="vendor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="">No vendor selected</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(old('vendor_id', $payment->vendor_id) === $vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Payee <span class="font-normal text-gray-400">(optional)</span></label><select name="payee_id" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="">No saved payee selected</option>@foreach($payees as $payee)<option value="{{ $payee->id }}" @selected(old('payee_id', $payment->payee_id) === $payee->id)>{{ $payee->name }} · {{ $payee->type }}</option>@endforeach</select></div>
                @endif
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Account Name <span class="text-red-500">*</span></label><select name="payment_account_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('payment_account_id', $payment->payment_account_id) === $account->id)>{{ $account->name }}{{ $account->code ? ' · '.$account->code : '' }}</option>@endforeach</select><p class="mt-1 text-xs text-gray-500">The same account is used for each schedule row.</p></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Payment Type <span class="text-red-500">*</span></label><select name="payment_type" x-model="paymentType" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="full">Full payment</option><option value="partial">Partial payment</option></select><p class="mt-1 text-xs text-gray-500" x-text="paymentType === 'full' ? 'One payment period for the net payable amount.' : 'Split the net payable amount across multiple periods.'"></p></div>
            </div>

            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3"><label class="flex items-center gap-2 text-sm font-semibold text-amber-900"><input type="hidden" name="tds_applied" value="0"><input type="checkbox" name="tds_applied" value="1" x-model="tdsApplied" class="rounded border-amber-300 text-amber-600"> Apply TDS deduction</label><label class="flex items-center gap-2 text-sm font-medium text-amber-900">TDS rate (%) <input type="number" name="tds_rate" x-model.number="rate" min="0" max="100" step="0.01" :disabled="!tdsApplied" class="w-28 rounded-lg border border-amber-300 bg-white px-3 py-2 text-right disabled:bg-amber-100"></label></div>
                <p class="mt-2 text-xs text-amber-800">TDS is calculated once from the payable amount. Only the net payable amount is sent for payment authorisation.</p>
            </div>

            <div class="mt-6 border-t border-gray-100 pt-5">
                <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="font-semibold text-gray-900">Payment schedule</h2><p class="mt-1 text-sm text-gray-500">Choose the month, week, and amount for each payment.</p></div><button type="button" x-show="paymentType === 'partial'" @click="addRow()" class="btn-secondary">+ Add payment period</button></div>
                <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Scheduled Month</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Scheduled Week</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Authorisation Channel</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Payment Amount (Rs)</th><th class="w-12 px-3 py-3"></th></tr></thead><tbody class="divide-y divide-gray-100"><template x-for="(row, index) in rows" :key="index"><tr><td class="px-4 py-3"><input type="month" name="schedule_month[]" x-model="row.month" required class="w-full rounded-lg border border-gray-300 px-3 py-2"></td><td class="px-4 py-3"><select name="schedule_week[]" x-model="row.week" required class="w-full rounded-lg border border-gray-300 px-3 py-2"><template x-for="week in [1,2,3,4,5]" :key="week"><option :value="week" x-text="'Week '+week"></option></template></select></td><td class="px-4 py-3"><select name="schedule_channel_id[]" x-model="row.channel" required class="w-full rounded-lg border border-gray-300 px-3 py-2"><option value="">Select channel</option>@foreach($channels as $channel)<option value="{{ $channel->id }}">{{ $channel->name }} · {{ $channel->approvalChain?->name }}</option>@endforeach</select></td><td class="px-4 py-3"><input type="number" name="schedule_amount[]" x-model.number="row.amount" :readonly="paymentType === 'full'" min="0.01" step="0.01" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-right font-mono read-only:bg-gray-50"></td><td class="px-3 py-3 text-right"><button type="button" x-show="paymentType === 'partial' && rows.length > 1" @click="removeRow(index)" class="btn-quiet text-red-600 hover:bg-red-50 hover:text-red-700">Remove</button></td></tr></template></tbody></table>
                </div>
                @if($channels->isEmpty())<p class="mt-3 text-sm text-amber-700">Create an active Payment Authorisation Channel in Administration → Approval Chains before scheduling payment.</p>@endif
                <div class="mt-4 flex flex-col gap-2 rounded-lg px-4 py-3 sm:flex-row sm:items-center sm:justify-between" :class="isReady ? 'bg-teal-50 text-teal-900' : 'bg-amber-50 text-amber-900'"><p class="text-sm font-semibold">Scheduled total: Rs <span x-text="format(scheduledTotal)"></span> <span class="font-normal">of Rs <span x-text="format(netPayable)"></span> remaining payable</span></p><p class="text-xs font-medium" x-text="isReady ? 'Ready to save' : 'Add or adjust amounts to match the net payable exactly'"></p></div>
                <p x-show="message" x-text="message" class="mt-2 text-sm font-medium text-red-600"></p>
            </div>

            <div class="mt-5"><label class="mb-1.5 block text-sm font-semibold text-gray-700">Notes</label><textarea name="notes" rows="2" class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2.5 text-sm">{{ old('notes', $payment->notes) }}</textarea></div>
        </section>
        <div class="mt-4 flex flex-wrap items-center gap-2"><button class="btn-primary" :disabled="!isReady" :class="!isReady && 'cursor-not-allowed opacity-50'">Save payment schedule</button>@can('payments.view')
<a href="{{ route('payments.index') }}" class="btn-quiet">Cancel</a>
@endcan</div>
    </form>
@endcan
</div>

<script>
function paymentSchedule(gross, startingRate, startingApplied, startingType, savedRows, defaultChannel) {
    const defaultRow = () => ({ month: new Date().toISOString().slice(0, 7), week: 1, channel: defaultChannel || '', amount: 0 });
    const rows = savedRows.length ? savedRows : [defaultRow()];
    return {
        gross, rate: Number(startingRate || 0), tdsApplied: Boolean(startingApplied), paymentType: startingType, rows, message: '',
        get tdsAmount() { return this.tdsApplied ? Math.round(this.gross * Number(this.rate || 0)) / 100 : 0; },
        get netPayable() { return Math.round((this.gross - this.tdsAmount - @json($lockedAmount)) * 100) / 100; },
        get scheduledTotal() { return Math.round(this.rows.reduce((total, row) => total + Number(row.amount || 0), 0) * 100) / 100; },
        get isReady() { return this.rows.length > 0 && Math.abs(this.scheduledTotal - this.netPayable) < 0.01; },
        format(value) { return Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        addRow() { this.rows.push(defaultRow()); },
        removeRow(index) { this.rows.splice(index, 1); },
        init() { this.$watch('paymentType', value => { if (value === 'full') this.rows = [this.rows[0] || defaultRow()]; if (value === 'partial' && this.rows.length === 1) this.rows.push(defaultRow()); this.syncFull(); }); this.$watch('netPayable', () => this.syncFull()); this.syncFull(); },
        syncFull() { if (this.paymentType === 'full' && this.rows[0]) this.rows[0].amount = this.netPayable; },
    };
}
</script>
@endsection
