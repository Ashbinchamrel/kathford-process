@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')

@section('content')
<div class="space-y-4">
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search action or label…"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        <input type="text" name="action" value="{{ request('action') }}" placeholder="Action filter (e.g. user.created)…"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        <div class="flex gap-2">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Record</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $log->logged_at->format('d M Y H:i') }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ $log->action }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-600 text-xs">{{ $log->model_label }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-400">{{ $log->ip_address }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.audit-logs.show', $log) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Details</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No audit logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $logs->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
