@extends('layouts.auth')
@section('title', 'Save Recovery Codes')

@section('content')
<h2 class="text-xl font-bold text-gray-900 mb-1">Save Your Recovery Codes</h2>
<p class="text-gray-500 text-sm mb-2">These codes can be used to sign in if you lose access to your authenticator app. <strong class="text-red-600">Each code can only be used once.</strong></p>

<div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 mb-6 text-sm text-yellow-800 flex items-start gap-2">
    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span>Store these codes in a safe place. They will <strong>not</strong> be shown again.</span>
</div>

<div class="bg-gray-900 rounded-xl p-5 mb-6 font-mono text-sm grid grid-cols-2 gap-2">
    @foreach($codes as $code)
        <span class="text-green-400 tracking-widest">{{ $code }}</span>
    @endforeach
</div>

<div class="flex gap-3">
    <a href="{{ route('dashboard') }}"
       class="flex-1 bg-teal-500 hover:bg-teal-600 text-white font-semibold py-3 rounded-xl transition-colors text-center">
        I've saved my codes – Continue
    </a>
</div>
@endsection
