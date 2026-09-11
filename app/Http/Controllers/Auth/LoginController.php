<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\TwoFactorTrust;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::active()->where('email', strtolower($credentials['email']))->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            if ($user) AuditLog::record($user, 'auth.credentials_failed');

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'The provided credentials are incorrect.']);
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

        if (! $user->hasTwoFactorEnabled()) {
            session(['2fa_setup_user_id' => $user->id]);
            return redirect()->route('2fa.setup');
        }

        if (TwoFactorTrust::isTrusted($request, $user)) {
            session(['2fa_passed' => true]);
            Auth::login($user);
            AuditLog::record($user, 'auth.login');
            return redirect()->intended(route('dashboard'));
        }

        session(['2fa_user_id' => $user->id]);
        AuditLog::record($user, 'auth.credentials_ok_pending_2fa');

        return redirect()->route('2fa.challenge');
    }
}
