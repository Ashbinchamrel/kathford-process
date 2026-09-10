@extends('layouts.app')
@section('title', 'Audit Log Detail')
@section('page-title', 'Audit Log Detail')

@section('content')
<div class="max-w-3xl space-y-5">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <dt class="text-xs font-semibold text-gray-400 uppercase mb-1">Time</dt>
                <dd class="text-gray-800">{{ $auditLog->logged_at->format('d M Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-gray-400 uppercase mb-1">User</dt>
                <dd class="text-gray-800">{{ $auditLog->user?->name ?? 'System' }}
                    @if($auditLog->user) <span class="text-gray-400 text-xs">· {{ $auditLog->user->email }}</span> @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-gray-400 uppercase mb-1">Action</dt>
                <dd><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $auditLog->action }}</span></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-gray-400 uppercase mb-1">IP Address</dt>
                <dd class="font-mono text-gray-700">{{ $auditLog->ip_address ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold text-gray-400 uppercase mb-1">Record</dt>
                <dd class="text-gray-700">{{ $auditLog->model_label ?? '—' }}</dd>
            </div>
            @if($auditLog->user_agent)
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold text-gray-400 uppercase mb-1">User Agent</dt>
                <dd class="text-gray-500 text-xs break-all">{{ $auditLog->user_agent }}</dd>
            </div>
            @endif
        </dl>
    </div>

    @if($auditLog->old_values || $auditLog->new_values)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-3">Changes</h2>
        <h3 class="text-sm font-medium text-gray-600 mb-2">Before</h3>
        <pre class="bg-gray-50 rounded-lg p-4 text-xs font-mono overflow-x-auto text-gray-700 mb-4">{{ json_encode($auditLog->old_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        <h3 class="text-sm font-medium text-gray-600 mb-2">After</h3>
        <pre class="bg-gray-50 rounded-lg p-4 text-xs font-mono overflow-x-auto text-gray-700">{{ json_encode($auditLog->new_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif

    <a href="{{ route('admin.audit-logs.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Back to Audit Logs</a>
</div>
@endsection
