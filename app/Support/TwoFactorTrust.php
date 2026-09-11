<?php

namespace App\Support;

use App\Models\TwoFactorTrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * "Remember this device for 30 days" — lets a user skip the recurring 2FA
 * code prompt on a browser they've already verified, without skipping the
 * password/Google login itself.
 */
class TwoFactorTrust
{
    public const COOKIE = 'kathford_2fa_remember';
    public const DAYS = 30;

    public static function isTrusted(Request $request, User $user): bool
    {
        $cookie = $request->cookie(self::COOKIE);
        if (! $cookie || ! str_contains($cookie, '|')) {
            return false;
        }

        [$selector, $validator] = explode('|', $cookie, 2);

        $device = TwoFactorTrustedDevice::where('user_id', $user->id)
            ->where('selector', $selector)
            ->where('expires_at', '>', now())
            ->first();

        return $device && hash_equals($device->hashed_validator, hash('sha256', $validator));
    }

    public static function remember(Request $request, User $user): void
    {
        $selector = Str::random(20);
        $validator = Str::random(40);

        TwoFactorTrustedDevice::create([
            'user_id'         => $user->id,
            'selector'        => $selector,
            'hashed_validator'=> hash('sha256', $validator),
            'user_agent'      => substr((string) $request->userAgent(), 0, 255),
            'ip_address'      => $request->ip(),
            'expires_at'      => now()->addDays(self::DAYS),
        ]);

        Cookie::queue(self::COOKIE, "{$selector}|{$validator}", 60 * 24 * self::DAYS);
    }

    public static function forget(User $user): void
    {
        TwoFactorTrustedDevice::where('user_id', $user->id)->delete();
    }
}
