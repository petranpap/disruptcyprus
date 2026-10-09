<?php

namespace App\Support\Preview;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * The pre-launch access cookie. Its value is an HMAC of the shared credentials keyed with APP_KEY, so it can't be
 * forged, and changing the password signs everyone out.
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

        return is_string($cookie) && $cookie !== '' && self::configured() && hash_equals(self::token(), $cookie);
    }

    public static function attempt(string $username, string $password): bool
    {
        if (! self::configured()) {
            return false;
        }

        // Compare both fields every time (no early exit) so timing doesn't reveal which one was wrong.
        $userOk = hash_equals((string) config('preview.username'), $username);
        $passOk = hash_equals((string) config('preview.password'), $password);

        return $userOk && $passOk;
    }

    public static function cookie(): Cookie
    {
        return Cookie::create(
            name: (string) config('preview.cookie'),
            value: self::token(),
            expire: now()->addDays((int) config('preview.lifetime_days')),
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

    private static function configured(): bool
    {
        return filled(config('preview.username')) && filled(config('preview.password'));
    }

    private static function token(): string
    {
        return hash_hmac('sha256', 'preview|'.config('preview.username').'|'.config('preview.password'), (string) config('app.key'));
    }
}
