<?php

namespace App\Support;

use App\Enums\ContentLocale;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Which content languages a request accepts: the user's content_locales, or both for guests.
 * The UI language (app locale, from Accept-Language) is always tried first when rendering an item.
 */
final class ContentLocales
{
    /**
     * @return list<string>
     */
    public static function accepted(Request $request): array
    {
        $user = $request->user();

        $locales = $user instanceof User && $user->content_locales !== []
            ? array_values(array_intersect(ContentLocale::values(), $user->content_locales))
            : ContentLocale::values();

        return $locales !== [] ? $locales : ContentLocale::values();
    }

    /**
     * Accepted locales ordered with the UI language first (used for fallback decisions).
     *
     * @return list<string>
     */
    public static function preferenceOrder(Request $request): array
    {
        $accepted = self::accepted($request);
        $current = app()->getLocale();

        return in_array($current, $accepted, true)
            ? array_values(array_unique([$current, ...$accepted]))
            : $accepted;
    }
}
