<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Author;
use App\Models\Digest;
use App\Models\Event;
use App\Models\Industry;
use App\Models\Section;
use App\Models\User;
use App\Services\Content\BookmarkState;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Per-request cache of the reader's saved items (see BookmarkState).
        $this->app->scoped(BookmarkState::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        // Short, stable type names in polymorphic columns and in the API ("article", "event").
        Relation::enforceMorphMap([
            'user' => User::class,
            'article' => Article::class,
            'event' => Event::class,
            'digest' => Digest::class,
            'industry' => Industry::class,
            'section' => Section::class,
            'author' => Author::class,
        ]);

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        ResetPassword::createUrlUsing(fn (User $user, string $token): string => config('app.frontend_url')
            .'/reset-password?'.http_build_query(['token' => $token, 'email' => $user->getEmailForPasswordReset()]));

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('views', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('exports', fn (Request $request) => Limit::perHour(1)->by((string) $request->user()?->id));
    }
}
