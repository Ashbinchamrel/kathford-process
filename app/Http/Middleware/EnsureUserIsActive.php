<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check 2FA is completed for this session.
        // '2fa_passed' is set by TwoFactorController after successful verification.
        if (! session('2fa_passed')) {
            // Do NOT logout here — they may be mid-2FA flow (e.g. on recovery codes page).
            // Just redirect to login so they can re-authenticate.
            return redirect()->route('login')
                ->withErrors(['error' => 'Please complete two-factor authentication.']);
        }

        // Check account is still active
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')
                ->withErrors(['error' => 'Your account has been deactivated. Contact your administrator.']);
        }

        return $next($request);
    }
}
