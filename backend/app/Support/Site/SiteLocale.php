<?php

namespace App\Support\Site;

use Illuminate\Http\Request;

/**
 * Language of a public page: `?lang=en|el`, Greek by default (the primary audience and the default for crawlers).
 * Share pages then fall back to whichever language the content actually exists in.
 */
final class SiteLocale
{
    public const DEFAULT = 'el';

    public const SUPPORTED = ['el', 'en'];

    public static function fromRequest(Request $request): string
    {
        $requested = $request->query('lang');

        return is_string($requested) && in_array($requested, self::SUPPORTED, true) ? $requested : self::DEFAULT;
    }

    public static function other(string $locale): string
    {
        return $locale === 'el' ? 'en' : 'el';
    }

    /**
     * Absolute URL of a public page in a language; the default language has no query string (canonical form).
     */
    public static function url(string $path, string $locale): string
    {
        $url = url($path);

        return $locale === self::DEFAULT ? $url : $url.'?lang='.$locale;
    }

    /**
     * Media URLs are root-relative (see config/filesystems.php); social previews and structured data need absolute ones.
     */
    public static function absolute(?string $url): ?string
    {
        return $url !== null && str_starts_with($url, '/') && ! str_starts_with($url, '//') ? url($url) : $url;
    }

    public static function appUrl(string $path = '/'): string
    {
        return rtrim((string) config('app.frontend_url'), '/').$path;
    }
}
