<?php

use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\Content\ArticleController;
use App\Http\Controllers\Api\V1\Content\BookmarkController;
use App\Http\Controllers\Api\V1\Content\DigestController;
use App\Http\Controllers\Api\V1\Content\EventController;
use App\Http\Controllers\Api\V1\Content\FeedController;
use App\Http\Controllers\Api\V1\Content\IndustryFeedController;
use App\Http\Controllers\Api\V1\Content\SearchController;
use App\Http\Controllers\Api\V1\Content\SectionArticlesController;
use App\Http\Controllers\Api\V1\IndustryController;
use App\Http\Controllers\Api\V1\Me\AvatarController;
use App\Http\Controllers\Api\V1\Me\DataExportController;
use App\Http\Controllers\Api\V1\Me\IndustriesController;
use App\Http\Controllers\Api\V1\Me\NotificationPreferencesController;
use App\Http\Controllers\Api\V1\Me\PasswordController;
use App\Http\Controllers\Api\V1\Me\ProfileController;
use App\Http\Controllers\Api\V1\Notifications\NotificationController;
use App\Http\Controllers\Api\V1\Notifications\PushSubscriptionController;
use App\Http\Controllers\Api\V1\SectionController;
use App\Http\Middleware\ResolveOptionalUser;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:api', ResolveOptionalUser::class])->group(function (): void {

    // Public taxonomy (cacheable, varies by Accept-Language)
    Route::middleware('cache.headers:public;max_age=300;etag')->group(function (): void {
        Route::get('sections', [SectionController::class, 'index'])->name('sections.index');
        Route::get('industries', [IndustryController::class, 'index'])->name('industries.index');
    });

    // Content (guests welcome; signed-in readers get bookmark state and their content languages)
    Route::middleware('content.cache:60')->group(function (): void {
        Route::get('feed/trending', [FeedController::class, 'trending'])->name('feed.trending');
        Route::get('sections/{slug}/articles', SectionArticlesController::class)->name('sections.articles');
        Route::get('industries/{slug}/feed', IndustryFeedController::class)->name('industries.feed');

        Route::get('articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
        Route::get('articles/{slug}/related', [ArticleController::class, 'related'])->name('articles.related');

        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/calendar', [EventController::class, 'calendar'])->name('events.calendar');
        Route::get('events/{slug}', [EventController::class, 'show'])->name('events.show');
        Route::get('events/{slug}/ics', [EventController::class, 'ics'])->name('events.ics');

        Route::get('digests', [DigestController::class, 'index'])->name('digests.index');
        Route::get('digests/latest', [DigestController::class, 'latest'])->name('digests.latest');
        Route::get('digests/{slug}', [DigestController::class, 'show'])->name('digests.show');

        Route::get('search', SearchController::class)->middleware('throttle:search')->name('search');
    });

    Route::get('push/public-key', [PushSubscriptionController::class, 'publicKey'])->name('push.public-key');

    Route::post('articles/{id}/view', [ArticleController::class, 'view'])
        ->whereNumber('id')->middleware('throttle:views')->name('articles.view');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('feed/for-you', [FeedController::class, 'forYou'])->middleware('content.cache:0')->name('feed.for-you');

        Route::get('bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
        Route::post('bookmarks', [BookmarkController::class, 'store'])->name('bookmarks.store');
        Route::delete('bookmarks', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->whereUuid('id')->name('notifications.read');

        Route::post('push/subscriptions', [PushSubscriptionController::class, 'store'])->name('push.subscriptions.store');
        Route::delete('push/subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push.subscriptions.destroy');
    });

    // Authentication
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', RegisterController::class)->middleware('throttle:auth')->name('register');
        Route::post('login', [SessionController::class, 'store'])->middleware('throttle:login')->name('login');
        Route::post('logout', [SessionController::class, 'destroy'])->middleware('auth:sanctum')->name('logout');

        Route::post('token', [TokenController::class, 'store'])->middleware('throttle:login')->name('token.store');
        Route::delete('token', [TokenController::class, 'destroy'])->middleware('auth:sanctum')->name('token.destroy');

        Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:auth')->name('password.forgot');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:auth')->name('password.reset');

        Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware(['auth:sanctum', 'throttle:auth'])->name('verification.send');

        // OAuth round trip needs the full web session (see SocialAuthController).
        Route::middleware('web')->whereIn('provider', SocialAuthController::PROVIDERS)->group(function (): void {
            Route::get('social/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
            Route::get('social/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
        });
    });

    // Signed links from emails (no session required)
    Route::get('auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')->whereNumber('id')->name('verification.verify');
    Route::get('me/export/{user}/{file}', [DataExportController::class, 'download'])
        ->middleware('signed')->name('me.export.download');

    // Account
    Route::middleware('auth:sanctum')->prefix('me')->name('me.')->group(function (): void {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

        Route::post('avatar', [AvatarController::class, 'store'])->name('avatar.store');
        Route::delete('avatar', [AvatarController::class, 'destroy'])->name('avatar.destroy');

        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('export', [DataExportController::class, 'store'])->middleware('throttle:exports')->name('export.store');

        Route::get('industries', [IndustriesController::class, 'show'])->name('industries.show');
        Route::put('industries', [IndustriesController::class, 'update'])->name('industries.update');

        Route::get('notification-preferences', [NotificationPreferencesController::class, 'show'])->name('preferences.show');
        Route::put('notification-preferences', [NotificationPreferencesController::class, 'update'])->name('preferences.update');
    });
});
