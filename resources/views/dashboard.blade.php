@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','Dashboard')
@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap justify-between gap-3"><div><h2 class="text-xl font-semibold">Welcome, {{ auth()->user()->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $workingFiscalYear?->name }} · Your work and assigned actions</p></div>@can('activity_forms.create')<a href="{{ route('activity-forms.create') }}" class="btn-primary">New Activity Form</a>@endcan</div>
    <details class="kcard p-5"><summary class="font-semibold text-sm cursor-pointer">Customise dashboard reports</summary><form method="POST" action="{{ route('dashboard.widgets') }}" class="mt-4 space-y-4">@csrf<div class="grid gap-3 sm:grid-cols-3">@forelse($available as $key=>$report)<label class="flex gap-2 text-sm"><input type="checkbox" name="widgets[]" value="{{ $key }}" @checked(in_array($key,$selected))>{{ $report[0] }}</label>@empty<p class="text-sm text-gray-500">No reports are available under your current permissions.</p>@endforelse</div><button class="btn-primary">Save dashboard</button></form></details>
    <div class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($reports as $key => $report)
        <section class="kcard overflow-hidden">
            <div class="p-4 {{ $report['action_required'] && $report['count'] ? 'bg-amber-50' : 'bg-white' }}">
                <h2 class="text-sm font-semibold text-gray-700">{{ $report['label'] }}</h2>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <p class="text-3xl font-bold tabular-nums {{ $report['action_required'] && $report['count'] ? 'text-amber-700' : 'text-slate-900' }}">{{ number_format($report['count']) }}</p>
                    <span class="text-xs text-gray-500">{{ $report['action_required'] ? ($report['count'] ? 'Needs your action' : 'You’re up to date') : 'This fiscal year' }}</span>
                </div>
            </div>
            @if($report['count'])
            <details class="border-t border-gray-100">
                <summary class="cursor-pointer px-4 py-3 text-xs font-semibold text-gray-600">{{ $report['action_required'] ? 'Preview pending items' : 'Preview latest items' }} · {{ count($report['rows']) }}</summary>
                <div class="divide-y divide-gray-100">
                    @foreach($report['rows'] as $row)
                    <a href="{{ $row['url'] }}" class="block px-4 py-3 hover:bg-gray-50">
                        <p class="truncate text-sm font-medium text-gray-800" title="{{ $row['title'] }}">{{ $row['title'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $row['status'] }}</p>
                    </a>
                    @endforeach
                </div>
                @if($report['count'] > count($report['rows']))<p class="px-4 pb-3 text-xs text-gray-400">Showing {{ count($report['rows']) }} of {{ $report['count'] }}.</p>@endif
            </details>
            @endif
            <a href="{{ $report['module_url'] }}" class="block border-t border-gray-100 px-4 py-3 text-xs font-semibold text-teal-700 hover:bg-teal-50">{{ $report['action_required'] ? 'Open workflow queue' : 'Open module' }} →</a>
        </section>
    @empty
        <div class="kcard p-6 text-sm text-gray-500 md:col-span-2">Choose reports under Customise dashboard reports to build your dashboard.</div>
    @endforelse
    </div>
</div>
@endsection
