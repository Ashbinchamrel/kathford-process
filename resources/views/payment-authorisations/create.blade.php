@extends('layouts.app')
@section('title', 'New Payment Authorisation')
@section('page-title', 'New Payment Authorisation')

@section('content')
<div class="max-w-6xl space-y-4">
    <form method="GET" class="flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-5">
        <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Scheduled Month</label><input type="month" name="month" value="{{ $month }}" class="rounded-lg border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Scheduled Week</label><select name="week" class="rounded-lg border-gray-300 px-3 py-2 text-sm">@for($i = 1; $i <= 5; $i++)<option value="{{ $i }}" @selected($week === $i)>Week {{ $i }}</option>@endfor</select></div>
        <button class="btn-secondary">Load scheduled payments</button>
        <p class="basis-full text-xs text-gray-500">Load every scheduled payment for one month and week. Selected payments are automatically separated into one authorisation per Authorisation Channel.</p>
    </form>

    @can('payment_authorisations.create')
<form method="POST" action="{{ route('payment-authorisations.store') }}">@csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
        <input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="week" value="{{ $week }}">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-6 py-5"><h2 class="font-semibold text-gray-900">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }} · Week {{ $week }}</h2><p class="mt-1 text-sm text-gray-500">Select schedules from any channel. The system creates a separate authorisation for each selected channel and links it to that channel’s approval chain.</p></div>
            @forelse($payments->groupBy(fn ($payment) => $payment->paymentAuthorisationChannel?->name ?: 'Unassigned channel') as $channelName => $channelPayments)
                <div class="border-b border-gray-100 last:border-b-0">
                    <div class="flex flex-wrap items-center justify-between gap-3 bg-teal-50 px-6 py-3"><div><h3 class="text-sm font-bold text-teal-950">{{ $channelName }}</h3><p class="mt-0.5 text-xs text-teal-800">Approval chain: {{ $channelPayments->first()->paymentAuthorisationChannel?->approvalChain?->name }}</p></div><p class="font-mono text-sm font-semibold text-teal-900">Rs {{ number_format($channelPayments->sum(fn ($payment) => $payment->net_amount ?: $payment->amount_due), 2) }}</p></div>
                    @foreach($channelPayments->groupBy(fn ($payment) => $payment->paymentAccount?->name ?: $payment->account_name ?: 'Unassigned account') as $accountName => $accountPayments)
                    <div class="border-t border-gray-100"><div class="flex justify-between bg-slate-50 px-6 py-2.5"><h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ $accountName }}</h4><p class="font-mono text-xs font-semibold text-slate-700">Rs {{ number_format($accountPayments->sum(fn ($payment) => $payment->net_amount ?: $payment->amount_due), 2) }}</p></div><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-white"><tr><th class="w-12 px-5 py-3"></th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Payment / Recipient</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Bill / Activity</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Gross</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Net payable</th></tr></thead><tbody class="divide-y divide-gray-100">@foreach($accountPayments as $payment)<tr class="hover:bg-gray-50"><td class="px-5 py-3"><input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" class="rounded border-gray-300 text-teal-600"></td><td class="px-5 py-3"><p class="font-mono text-xs font-bold text-gray-800">{{ $payment->payment_number }}</p><p class="mt-0.5 text-gray-600">{{ $payment->vendor?->name ?: $payment->payee?->name ?: 'External payment' }}</p></td><td class="px-5 py-3 text-gray-600">{{ $payment->bill_number ?: '—' }}<br><span class="text-xs">{{ $payment->activity_name ?: $payment->activity_reference ?: '—' }}</span></td><td class="px-5 py-3 text-right font-mono text-gray-600">Rs {{ number_format($payment->amount_due, 2) }}</td><td class="px-5 py-3 text-right font-mono font-semibold text-teal-800">Rs {{ number_format($payment->net_amount ?: $payment->amount_due, 2) }}</td></tr>@endforeach</tbody></table></div></div>
                    @endforeach
                </div>
            @empty
                <div class="px-6 py-12 text-center text-gray-400">No scheduled payments are available for this month and week.</div>
            @endforelse
        </section>
        <section class="mt-4 rounded-xl border border-gray-200 bg-white p-5"><label class="block text-sm font-semibold text-gray-700">Authorisation notes<textarea name="notes" rows="2" class="mt-1.5 w-full rounded-lg border-gray-300 text-sm"></textarea></label><p class="mt-3 text-sm text-gray-500">Each selected channel keeps its own authorisation and approval chain.</p><div class="mt-4 flex flex-wrap items-center gap-2"><button class="btn-secondary">Save authorisation(s)</button><button name="submit_for_approval" value="1" class="btn-primary">Create & submit selected authorisation(s)</button>@can('payment_authorisations.view')
<a href="{{ route('payment-authorisations.index') }}" class="btn-quiet">Cancel</a>
@endcan</div></section>
    </form>
@endcan
</div>
@endsection
