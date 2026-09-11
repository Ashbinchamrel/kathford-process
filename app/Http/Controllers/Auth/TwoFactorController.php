<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\TwoFactorTrust;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ── 2FA Setup (first time) ───────────────────────────────

    public function showSetup(): View|RedirectResponse
    {
        $userId = session('2fa_setup_user_id');
        if (! $userId) return redirect()->route('login');

        $user = User::findOrFail($userId);

        // Generate a new secret
        $secret = session('2fa_secret') ?: $this->google2fa->generateSecretKey(32);
        session(['2fa_secret' => $secret]);

        $qrContent = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        // Generate QR code as inline SVG
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($qrContent);

        return view('auth.2fa-setup', compact('user', 'secret', 'qrSvg'));
    }

    public function confirmSetup(Request $request): RedirectResponse
    {
        $userId = session('2fa_setup_user_id');
        $secret = session('2fa_secret');

        if (! $userId || ! $secret) return redirect()->route('login');

        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $user = User::findOrFail($userId);
        $valid = $this->google2fa->verifyKey($secret, $request->code, 1);

        if (! $valid) {
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        $user->update([
            'two_factor_secret'        => encrypt($secret),
            'two_factor_confirmed_at'  => now(),
        ]);

        session()->forget(['2fa_setup_user_id', '2fa_secret']);
        session(['2fa_passed' => true]);

        if ($request->boolean('remember_device')) {
            TwoFactorTrust::remember($request, $user);
        }

        Auth::login($user);
        AuditLog::record($user, 'auth.2fa_setup_complete');

        return redirect()->route('dashboard');
    }

    // ── 2FA Challenge (each login) ───────────────────────────

    public function showChallenge(): View|RedirectResponse
    {
        if (! session('2fa_user_id')) return redirect()->route('login');
        return view('auth.2fa-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $userId = session('2fa_user_id');
        if (! $userId) return redirect()->route('login');

        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $user   = User::findOrFail($userId);
        $secret = decrypt($user->two_factor_secret);

        $valid = $this->google2fa->verifyKey($secret, $request->code, 1);

        if (! $valid) {
            AuditLog::record($user, 'auth.2fa_failed');
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        session()->forget('2fa_user_id');
        session(['2fa_passed' => true]); // Mark 2FA as completed for this session

        if ($request->boolean('remember_device')) {
            TwoFactorTrust::remember($request, $user);
        }

        Auth::login($user);

        AuditLog::record($user, 'auth.login');
        return redirect()->intended(route('dashboard'));
    }

    // ── Logout ───────────────────────────────────────────────

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::record(Auth::user(), 'auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
