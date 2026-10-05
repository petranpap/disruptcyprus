<?php

use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\IndustryController;
use App\Http\Controllers\Api\V1\Me\AvatarController;
use App\Http\Controllers\Api\V1\Me\DataExportController;
use App\Http\Controllers\Api\V1\Me\IndustriesController;
use App\Http\Controllers\Api\V1\Me\NotificationPreferencesController;
use App\Http\Controllers\Api\V1\Me\PasswordController;
use App\Http\Controllers\Api\V1\Me\ProfileController;
use App\Http\Controllers\Api\V1\SectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {

    // Public taxonomy (cacheable, varies by Accept-Language)
    Route::middleware('cache.headers:public;max_age=300;etag')->group(function (): void {
        Route::get('sections', [SectionController::class, 'index'])->name('sections.index');
        Route::get('industries', [IndustryController::class, 'index'])->name('industries.index');
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
