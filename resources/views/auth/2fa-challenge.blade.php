@extends('layouts.auth')
@section('title', 'Two-Factor Authentication')

@section('content')
<div class="mb-6">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:#E6F7F6;">
        <svg class="w-5 h-5" style="color:#00A99D;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900 mb-1">Two-factor verification</h2>
    <p class="text-sm text-gray-500">Enter the 6-digit code from your authenticator app to continue.</p>
</div>

@if($errors->any())
    <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background:#FFF1F2;border:1px solid #FECDD3;color:#991B1B;">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('2fa.verify') }}">
    @csrf
    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Authentication Code</label>
    <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
           placeholder="000 000" autofocus autocomplete="one-time-code"
           class="w-full text-center text-2xl font-mono tracking-widest rounded-lg px-4 py-3 outline-none mb-4"
           style="border:1.5px solid #D1D5DB;letter-spacing:0.2em;"
           onfocus="this.style.borderColor='#00A99D';" onblur="this.style.borderColor='#D1D5DB';">
    <label class="flex items-center gap-2 text-sm text-gray-600 mb-4">
        <input type="checkbox" name="remember_device" value="1" class="rounded text-teal-500">
        Remember this device for 30 days
    </label>
    <button type="submit" class="w-full py-3 rounded-lg font-semibold text-white text-sm transition-colors"
            style="background:#00A99D;" onmouseover="this.style.background='#008C82';" onmouseout="this.style.background='#00A99D';">Verify</button>
</form>
@endsection
