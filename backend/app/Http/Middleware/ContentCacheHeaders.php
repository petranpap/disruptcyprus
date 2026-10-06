<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache-Control + ETag for content GETs. Guests get a short public cache; signed-in users get a
 * private response (it contains `is_bookmarked`) that still revalidates cheaply with If-None-Match.
 */
class ContentCacheHeaders
{
    public function handle(Request $request, Closure $next, string $maxAge = '60'): Response
    {
        $response = $next($request);

        if (! $request->isMethodCacheable() || $response->getStatusCode() !== 200 || $response->headers->has('Content-Disposition')) {
            return $response;
        }

        if ($request->user() === null) {
            $response->setPublic();
            $response->setMaxAge((int) $maxAge);
        } else {
            $response->setPrivate();
            $response->headers->addCacheControlDirective('no-cache');
        }

        $response->setVary(array_unique([...$response->getVary(), 'Accept-Language', 'Cookie', 'Authorization']));
        $response->setEtag(hash('xxh128', (string) $response->getContent()));
        $response->isNotModified($request);

        return $response;
    }
}
