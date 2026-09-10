@extends('layouts.app')
@section('title', 'Activity Forms')
@section('page-title', 'Activity Forms')

@section('content')
@php
    $tabs = ['' => 'All forms', 'draft' => 'Drafts', 'pending_verification' => 'Verification', 'verified' => 'Verified', 'pending_approval' => 'Approval', 'approved' => 'Approved', 'rejected' => 'Returned'];
    $currentStatus = request('status', '');
    $statusStyle = fn ($status) => match ($status) {
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'pending_verification', 'pending_approval' => 'bg-amber-50 text-amber-800 ring-amber-100',
        'verified' => 'bg-sky-50 text-sky-700 ring-sky-100',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

<div class="mx-auto max-w-[1600px] space-y-3">
@can('activity_forms.create')<div class="flex justify-end gap-3"><a href="{{ route('activity-forms.create') }}" class="btn-primary">+ New activity form</a></div>@endcan

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_280px_auto_auto] lg:items-end">
            <input type="hidden" name="status" value="{{ $currentStatus }}">
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find a form</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="Form number or activity name" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Category</span><select name="category_id" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"><option value="">All categories</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>@endforeach</select></label>
            <button class="btn-primary h-[42px] justify-center">Apply filters</button>
            @if(request()->hasAny(['search','category_id','status']))@can('activity_forms.view')
<a href="{{ route('activity-forms.index') }}" class="h-[42px] px-3 py-3 text-center text-sm font-medium text-gray-500 hover:text-gray-800">Reset</a>
@endcan
@endif
        </form>
        <div class="mt-4 -mx-1 overflow-x-auto px-1"><div class="flex min-w-max gap-1.5 border-t border-gray-100 pt-3">@foreach($tabs as $value => $label)@can('activity_forms.view')
<a href="{{ route('activity-forms.index', array_merge(request()->except('status','page'), $value ? ['status' => $value] : [])) }}" class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $currentStatus === $value ? 'bg-[#0B1E3D] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">{{ $label }}</a>
@endcan
@endforeach</div></div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2"><div><h3 class="font-semibold text-gray-900">{{ $tabs[$currentStatus] ?? 'Activity forms' }}</h3><p class="mt-0.5 text-sm text-gray-500">{{ $forms->total() }} record{{ $forms->total() === 1 ? '' : 's' }} found</p></div><p class="hidden text-xs text-gray-400 sm:block">Amber markers require your action</p></div>
        <x-super-admin-bulk-delete module="activity_forms" />
        <div class="overflow-x-auto"><table class="min-w-[1040px] w-full text-sm"><thead class="border-b border-gray-100 bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500"><tr>@if(auth()->user()->isSuperAdmin())<th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all activity forms" data-bulk-delete-toggle="activity_forms"></th>@endif<th class="w-40 px-3 py-2">Form</th><th class="min-w-[240px] px-3 py-2">Activity</th><th class="w-48 px-3 py-2">Category</th><th class="w-44 px-3 py-2">Workflow</th><th class="w-36 px-3 py-2">Deadline</th><th class="w-36 px-3 py-2 text-right">Amount</th><th class="w-28 px-3 py-2"></th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($forms as $form)@php($needsAction = ($form->isPendingVerification() && auth()->user()->can('verify', $form)) || ($form->isPendingApproval() && auth()->user()->can('finalApprove', $form)))<tr class="group transition hover:bg-slate-50 {{ $needsAction ? 'bg-amber-50/40' : '' }}">@if(auth()->user()->isSuperAdmin())<td class="px-3 py-4"><input type="checkbox" value="{{ $form->id }}" aria-label="Select {{ $form->form_number }}" data-bulk-delete-record="activity_forms"></td>@endif<td class="px-3 py-2"><div class="flex items-center gap-2"><span class="font-mono font-bold text-slate-800">{{ $form->form_number }}</span>@if($needsAction)<span class="h-2 w-2 rounded-full bg-amber-400" title="Action required"></span>@endif</div><p class="mt-1 text-xs text-slate-400">{{ $form->creator?->name ?? '—' }}</p></td><td class="px-3 py-2"><p class="max-w-[320px] truncate font-semibold text-slate-800" title="{{ $form->activity_name }}">{{ $form->activity_name }}</p><p class="mt-1 truncate text-xs text-slate-500">{{ $form->department?->name ?? 'No department' }}</p></td><td class="px-3 py-2"><span class="inline-flex max-w-[175px] truncate rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600" title="{{ $form->category?->name }}">{{ $form->category?->name ?? 'Uncategorised' }}</span></td><td class="px-3 py-2"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusStyle($form->status) }}">{{ $form->statusLabel() }}</span></td><td class="px-3 py-2 text-slate-600">@if($form->deadline_date)<p class="font-medium {{ $form->deadline_date->isPast() && ! $form->isApproved() ? 'text-rose-600' : '' }}">{{ $form->deadline_date->format('d M Y') }}</p>@if($form->deadline_date->isToday())<p class="mt-1 text-xs text-amber-600">Due today</p>@endif @else <span class="text-slate-400">No deadline</span> @endif</td><td class="px-3 py-2 text-right"><span class="whitespace-nowrap font-mono font-semibold text-slate-800">{{ $form->total_estimated_amount ? 'Rs '.number_format($form->total_estimated_amount, 2) : '—' }}</span></td><td class="px-3 py-2 text-right">@can('activity_forms.view')
<a href="{{ route('activity-forms.show', $form) }}" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold {{ $needsAction ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-teal-50 text-teal-700 hover:bg-teal-100' }}">{{ $needsAction ? 'Review' : 'Open' }} <span class="ml-1">→</span></a>
@endcan</td></tr>@empty<tr><td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="px-5 py-16 text-center"><p class="font-medium text-slate-600">No matching activity forms</p><p class="mt-1 text-sm text-slate-400">Try changing the filters or create a new activity form.</p></td></tr>@endforelse</tbody></table></div>
        @if($forms->hasPages())<div class="border-t border-gray-100 px-3 py-2">{{ $forms->withQueryString()->links() }}</div>@endif
    </section>
</div>
@endsection
