<?php

use App\Http\Controllers\AdminLanguageController;
use App\Http\Controllers\Site\ComingSoonController;
use App\Http\Controllers\Site\LandingController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ShareController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
| Public site on the main domain: landing, share pages behind every shared link, static pages, SEO files.
*/
// Stateless: no session or CSRF cookie, so pages are cacheable and visitors aren't tracked.
$stateless = [StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class, AddQueuedCookiesToResponse::class];

// `preview` (pre-launch gate) runs outermost so it can replace the page and the cache headers. See config/preview.php.
Route::middleware(['preview:site', 'cache.headers:public;max_age=300;etag', 'security:site'])->withoutMiddleware($stateless)->group(function (): void {
    Route::get('/', LandingController::class)->name('site.home');
    Route::get('en', fn () => app(LandingController::class)('en'))->name('site.home.en');

    Route::get('a/{slug}', [ShareController::class, 'article'])->name('site.article');
    Route::get('e/{slug}', [ShareController::class, 'event'])->name('site.event');
    Route::get('d/{slug}', [ShareController::class, 'digest'])->name('site.digest');

    Route::get('{page}', PageController::class)->whereIn('page', PageController::PAGES)->name('site.page');

    Route::get('sitemap.xml', [SitemapController::class, 'sitemap'])->name('site.sitemap');
});

Route::middleware('security:site')->withoutMiddleware($stateless)->group(function (): void {
    Route::get('robots.txt', [SitemapController::class, 'robots'])->name('site.robots');

    // Waitlist form on the coming-soon page. Stateless page, so no CSRF token: rate-limited, honeypot, idempotent.
    Route::post('waitlist', [ComingSoonController::class, 'join'])->middleware('throttle:5,1')->name('waitlist.join');
});

Route::get('admin-language/{locale}', AdminLanguageController::class)
    ->middleware('auth')
    ->name('admin.language');
