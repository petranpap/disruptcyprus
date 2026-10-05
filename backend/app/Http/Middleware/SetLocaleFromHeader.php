<?php

namespace App\Http\Middleware;

use App\Enums\ContentLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale from Accept-Language (el|en). Defaults to the app locale (el).
 */
class SetLocaleFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ContentLocale::values();
        $preferred = $request->getPreferredLanguage($supported);

        // Symfony returns the first supported locale when nothing matches; use the app default instead.
        $locale = $preferred !== null && $this->acceptsLanguage($request, $preferred) ? $preferred : config('app.locale');

        app()->setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);
        $response->setVary(array_unique([...$response->getVary(), 'Accept-Language']));

        return $response;
    }

    private function acceptsLanguage(Request $request, string $locale): bool
    {
        foreach ($request->getLanguages() as $language) {
            if (str_starts_with(strtolower($language), $locale)) {
                return true;
            }
        }

        return false;
    }
}
