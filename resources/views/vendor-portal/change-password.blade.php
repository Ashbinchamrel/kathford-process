<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Change Password - Vendor Portal</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-slate-50 p-4">
    <main class="mx-auto max-w-md pt-12">
        <div class="mb-5 flex items-center justify-between gap-3"><a href="{{ route('vendor.portal.dashboard') }}" class="text-sm font-medium text-teal-700">← Back to portal</a><a href="{{ route('vendor.portal.profile.edit') }}" class="text-sm font-medium text-teal-700">My profile</a></div>
        <form method="POST" action="{{ route('vendor.portal.password.update') }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            @csrf
            <div><h1 class="text-xl font-bold">Change password</h1><p class="mt-1 text-sm text-slate-500">Signed in as {{ $vendor->email }}</p></div>
            <div><label class="mb-1 block text-sm font-medium">Current password</label><input type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1 block text-sm font-medium">New password</label><input type="password" name="password" required minlength="10" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1 block text-sm font-medium">Confirm new password</label><input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Update password</button>
        </form>
    </main>
</body>
</html>
