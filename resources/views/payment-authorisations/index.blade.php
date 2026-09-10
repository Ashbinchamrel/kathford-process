@extends('layouts.app')

@section('title', 'Payment Authorisation')
@section('page-title', 'Payment Authorisation')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-3">
    @can('payment_authorisations.create')<div class="flex justify-end gap-3"><a href="{{ route('payment-authorisations.create') }}" class="btn-primary">+ New authorisation</a></div>@endcan
    @php
        $tabs = ['' => 'All authorisations', 'generated' => 'Ready to submit', 'pending_verification' => 'Verification', 'pending_approval' => 'Approval', 'approved' => 'Approved', 'rejected' => 'Returned / rejected'];
        $currentStatus = (string) request('status', '');
    @endphp
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_210px_auto] lg:items-end">
            <input type="hidden" name="status" value="{{ $currentStatus }}">
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find an authorisation</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="Authorisation number, channel or approval chain" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Scheduled month</span><input type="month" name="month" value="{{ request('month') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"></label>
            <div class="flex gap-2"><button class="btn-primary h-[42px] justify-center">Apply filters</button>@if(request()->filled('search') || request()->filled('month') || request()->filled('status'))<a href="{{ route('payment-authorisations.index') }}" class="h-[42px] px-3 py-3 text-sm text-gray-500">Reset</a>@endif</div>
        </form>
        <div class="mt-4 -mx-1 overflow-x-auto px-1"><div class="flex min-w-max gap-1.5 border-t border-gray-100 pt-3">
            @foreach($tabs as $value => $label)
            <a href="{{ route('payment-authorisations.index', array_merge(request()->except('status','page'), $value ? ['status' => $value] : [])) }}" @if($currentStatus === $value) aria-current="page" @endif class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $currentStatus === $value ? 'bg-[#0B1E3D] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">{{ $label }}</a>
            @endforeach
        </div></div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
            <div>
                <h2 class="font-semibold text-gray-900">Authorisation queue</h2>
                <p class="mt-0.5 text-sm text-gray-500">{{ $authorisations->total() }} authorisation{{ $authorisations->total() === 1 ? '' : 's' }}</p>
            </div>
        </div>

        <x-super-admin-bulk-delete module="payment_authorisations" />

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        @if(auth()->user()->isSuperAdmin())
                            <th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all authorisations" data-bulk-delete-toggle="payment_authorisations"></th>
                        @endif
                        <th class="px-3 py-2">Authorisation</th>
                        <th class="px-3 py-2">Channel / chain</th>
                        <th class="px-3 py-2">Scheduled period</th>
                        <th class="px-3 py-2 text-right">Payments</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($authorisations as $authorisation)
                        <tr class="transition hover:bg-slate-50">
                            @if(auth()->user()->isSuperAdmin())
                                <td class="px-3 py-4"><input type="checkbox" value="{{ $authorisation->id }}" aria-label="Select {{ $authorisation->authorisation_number }}" data-bulk-delete-record="payment_authorisations"></td>
                            @endif
                            <td class="px-3 py-2"><p class="font-mono text-xs font-bold text-slate-900">{{ $authorisation->authorisation_number }}</p><p class="mt-1 text-xs text-slate-400">Created {{ $authorisation->created_at?->format('d M Y') }}</p></td>
                            <td class="px-3 py-2"><p class="font-medium text-slate-900">{{ $authorisation->paymentAuthorisationChannel?->name ?: 'Legacy channel' }}</p><p class="mt-1 text-xs text-slate-400">{{ $authorisation->approvalChain?->name ?: 'No active chain' }}</p></td>
                            <td class="px-3 py-2 text-slate-700">{{ $authorisation->schedule_month?->format('F Y') ?: '—' }} · Week {{ $authorisation->schedule_week }}</td>
                            <td class="px-3 py-2 text-right font-mono text-slate-700">{{ $authorisation->payments->count() }}</td>
                            <td class="px-3 py-2 text-right font-mono font-semibold text-slate-900">Rs {{ number_format($authorisation->total_amount, 2) }}</td>
                            <td class="px-3 py-2">
                                @php($statusClass = match($authorisation->status) { 'approved' => 'bg-emerald-100 text-emerald-800', 'pending_verification', 'pending_approval' => 'bg-amber-100 text-amber-800', 'rejected' => 'bg-rose-100 text-rose-800', default => 'bg-slate-100 text-slate-700' })
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $authorisation->statusLabel() }}</span>
                                @if($step = app(\App\Services\ApprovalService::class)->pendingStepLabel($authorisation))<p class="mt-1 text-xs text-amber-800">{{ $step }}</p>@endif
                            </td>
                            <td class="px-3 py-2 text-right">@can('payment_authorisations.view')
<a href="{{ route('payment-authorisations.show', $authorisation) }}" class="font-semibold text-teal-700 hover:text-teal-800">View <span aria-hidden="true">→</span></a>
@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="px-5 py-14 text-center text-sm text-gray-400">No authorisations match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($authorisations->hasPages())
            <div class="border-t border-gray-100 px-3 py-2">{{ $authorisations->links() }}</div>
        @endif
    </section>
</div>
@endsection
