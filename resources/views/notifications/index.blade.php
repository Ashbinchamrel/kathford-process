@extends('layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="max-w-3xl space-y-4">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $notifications->total() }} notification{{ $notifications->total() !== 1 ? 's' : '' }}</p>
        @if($notifications->where('is_read', false)->count())
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button class="text-teal-600 hover:text-teal-700 text-sm font-medium">Mark all as read</button>
        </form>
        @endif
    </div>

    <div class="space-y-2">
        @forelse($notifications as $notif)
        <div class="bg-white rounded-xl border {{ $notif->is_read ? 'border-gray-200' : 'border-teal-300 shadow-sm' }} p-4 flex items-start gap-4">
            <div class="w-9 h-9 rounded-full {{ $notif->is_read ? 'bg-gray-100' : 'bg-teal-50' }} flex items-center justify-center flex-shrink-0">
                @switch($notif->type)
                    @case('form_submitted') 📋 @break
                    @case('form_approved') ✅ @break
                    @case('form_rejected') ❌ @break
                    @case('form_verified') 🔍 @break
                    @case('rfq_received') 💼 @break
                    @case('po_issued') 📦 @break
                    @case('payment_due') 💰 @break
                    @default 🔔
                @endswitch
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-800 {{ $notif->is_read ? '' : 'font-medium' }}">{{ $notif->message }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex-shrink-0 flex items-center gap-3">
                @if(!$notif->is_read)
                <form method="POST" action="{{ route('notifications.mark-read', $notif) }}">
                    @csrf
                    <button class="text-xs text-teal-600 hover:text-teal-700 font-medium">Mark read</button>
                </form>
                @endif
                @if($notif->link)
                <form method="POST" action="{{ route('notifications.mark-read', ['notification' => $notif->id, 'redirect' => 1]) }}">
                    @csrf
                    <button class="text-xs text-gray-500 hover:text-gray-700">View →</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
            <p class="text-gray-400 text-sm">You have no notifications.</p>
        </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
