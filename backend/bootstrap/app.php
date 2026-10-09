<?php

use App\Http\Middleware\ContentCacheHeaders;
use App\Http\Middleware\PreviewGate;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocaleFromHeader;
use App\Support\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA cookie auth for requests coming from the PWA origin.
        $middleware->statefulApi();
        $middleware->api(prepend: [PreviewGate::class.':api', SetLocaleFromHeader::class]);
        // Read by Apache on the app host (presence only) and verified here; must stay unencrypted.
        $middleware->encryptCookies(except: ['dc_preview']);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias(['content.cache' => ContentCacheHeaders::class, 'security' => SecurityHeaders::class, 'preview' => PreviewGate::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(new ApiExceptionRenderer);
    })->create();
