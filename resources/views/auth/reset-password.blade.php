@extends('layouts.auth')
@section('title', 'Choose a New Password')

@section('content')
<h2 class="text-2xl font-bold text-gray-900 mb-1">Choose a new password</h2>
<p class="text-sm text-gray-500 mb-7">Use at least 12 characters.</p>

@if($errors->any())
    <div class="mb-5 rounded-lg px-4 py-3 text-sm" style="background:#FFF1F2;border:1px solid #FECDD3;color:#991B1B;">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
               class="w-full rounded-lg px-3 py-2.5 text-sm outline-none" style="border:1.5px solid #D1D5DB;">
    </div>
    <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password"
               class="w-full rounded-lg px-3 py-2.5 text-sm outline-none" style="border:1.5px solid #D1D5DB;">
    </div>
    <div class="mb-5">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm new password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
               class="w-full rounded-lg px-3 py-2.5 text-sm outline-none" style="border:1.5px solid #D1D5DB;">
    </div>
    <button type="submit" class="w-full py-3 rounded-lg font-semibold text-white text-sm transition-colors"
            style="background:#00A99D;" onmouseover="this.style.background='#008C82';" onmouseout="this.style.background='#00A99D';">Reset password</button>
</form>
@endsection
