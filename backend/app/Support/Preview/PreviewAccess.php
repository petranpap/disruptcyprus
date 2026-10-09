<?php

namespace App\Support\Preview;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Pre-launch access for staff. Signing in to the admin panel issues a personal cookie "{user id}.{expiry}.{signature}"
 * (HMAC with APP_KEY): it can't be forged or extended, and it stops working as soon as the account is no longer staff.
 * There is no shared password.
 */
final class PreviewAccess
{
    public static function enabled(): bool
    {
        return (bool) config('preview.enabled');
    }

    public static function granted(Request $request): bool
    {
        $cookie = $request->cookie((string) config('preview.cookie'));
        if (! is_string($cookie) || substr_count($cookie, '.') !== 2) {
            return false;
        }

        [$userId, $expires, $signature] = explode('.', $cookie);
        if (! ctype_digit($userId) || ! ctype_digit($expires) || (int) $expires < now()->getTimestamp()) {
            return false;
        }
        if (! hash_equals(self::sign($userId, $expires), $signature)) {
            return false;
        }

        // Revocable: demoted or deleted staff lose access immediately.
        return User::query()->whereKey((int) $userId)->first()?->role->canAccessAdmin() ?? false;
    }

    public static function cookieFor(User $user): Cookie
    {
        $expires = (string) now()->addDays((int) config('preview.lifetime_days'))->getTimestamp();
        $userId = (string) $user->getKey();

        return Cookie::create(
            name: (string) config('preview.cookie'),
            value: $userId.'.'.$expires.'.'.self::sign($userId, $expires),
            expire: (int) $expires,
            path: '/',
            domain: config('preview.cookie_domain') ?: null,
            secure: request()->isSecure(),
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }

    public static function forget(): Cookie
    {
        return Cookie::create((string) config('preview.cookie'), '', 1, '/', config('preview.cookie_domain') ?: null);
    }

    private static function sign(string $userId, string $expires): string
    {
        return hash_hmac('sha256', "preview|{$userId}|{$expires}", (string) config('app.key'));
    }
}
