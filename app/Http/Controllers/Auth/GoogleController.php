<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\TwoFactorTrust;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->stateless()
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google OAuth callback failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('login')
                ->withErrors(['google' => 'Google sign-in failed. Please try again or contact your administrator.']);
        }

        // ── Domain restriction ────────────────────────────────
        $allowedDomain = config('kathford.allowed_email_domain');
        $emailDomain = substr(strrchr($googleUser->getEmail(), '@'), 1);

        if ($emailDomain !== $allowedDomain) {
            AuditLog::record(null, 'auth.rejected_domain', null, $googleUser->getEmail());
            return redirect()->route('login')
                ->withErrors(['google' => "Only {$allowedDomain} accounts are allowed."]);
        }

        // ── Find or refuse user (Super Admin creates accounts) ─
        $user = User::where('email', $googleUser->getEmail())
                    ->where('is_active', true)
                    ->first();

        if (! $user) {
            AuditLog::record(null, 'auth.rejected_no_account', null, $googleUser->getEmail());
            return redirect()->route('login')
                ->withErrors(['google' => 'No active account found for this email. Please contact your administrator.']);
        }

        // ── Update Google profile data ────────────────────────
        $user->update([
            'google_id'   => $googleUser->getId(),
            'avatar'      => $googleUser->getAvatar(),
            'name'        => $user->name ?: $googleUser->getName(),
            'last_login_at'=> now(),
            'last_login_ip'=> request()->ip(),
        ]);

        // ── Check if 2FA is set up ────────────────────────────
        if (! $user->hasTwoFactorEnabled()) {
            // Store Google user in session and redirect to 2FA setup
            session(['2fa_setup_user_id' => $user->id]);
            return redirect()->route('2fa.setup');
        }

        // ── Trusted device — skip the repeated 2FA prompt ─────
        if (TwoFactorTrust::isTrusted($request, $user)) {
            session(['2fa_passed' => true]);
            Auth::login($user);
            AuditLog::record($user, 'auth.login');
            return redirect()->intended(route('dashboard'));
        }

        // ── 2FA already configured — require code ────────────
        session(['2fa_user_id' => $user->id]);
        AuditLog::record($user, 'auth.google_ok_pending_2fa');
        return redirect()->route('2fa.challenge');
    }
}
