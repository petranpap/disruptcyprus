<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline headers on every Laravel response. HSTS is set by Apache on the TLS vhosts (docs/DEPLOYMENT.md),
 * and the PWA's static files get their CSP there too.
 */
class SecurityHeaders
{
    /**
     * Public Blade pages: no inline scripts (JSON-LD is a data block, not executed), self-hosted fonts and CSS.
     * Inline style attributes carry industry colours, hence 'unsafe-inline' for styles only.
     */
    public const SITE_CSP = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; "
        ."font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    public function handle(Request $request, Closure $next, string $profile = 'default'): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if (! $response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if ($profile === 'site') {
            $response->headers->set('Content-Security-Policy', self::SITE_CSP);
        }

        return $response;
    }
}
