<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Site\ComingSoonController;
use App\Support\Preview\PreviewAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pre-launch gate (config/preview.php). `site` mode answers with the coming-soon page, `api` mode with 403.
 * While the gate is on, nothing behind it may be cached by shared caches or replayed from the browser cache.
 */
class PreviewGate
{
    public function handle(Request $request, Closure $next, string $mode = 'site'): Response
    {
        if (! PreviewAccess::enabled()) {
            return $next($request);
        }

        $response = PreviewAccess::granted($request)
            ? $next($request)
            : ($mode === 'api'
                ? response()->json(['message' => 'Disrupt Cyprus is launching soon.', 'code' => 'preview_locked'], 403)
                : app(ComingSoonController::class)->show($request));

        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
