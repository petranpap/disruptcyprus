<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\UnverifiedSocialEmail;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Account\SocialLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * OAuth sign-in. These routes run in the "web" middleware group so the session (and OAuth state)
 * survives the round trip to the provider, whose callback request is not "stateful" to Sanctum.
 */
class SocialAuthController extends Controller
{
    public const PROVIDERS = ['google'];

    public function redirect(Request $request, string $provider): SymfonyRedirectResponse|RedirectResponse
    {
        if (! filled(config("services.{$provider}.client_id"))) {
            return $this->backToSignIn('social_unavailable');
        }

        // Where to return after sign-in (e.g. the shared article the reader came from). In-app paths only.
        $next = $request->query('next');
        if (is_string($next) && preg_match('#^/(?![/\\\\])[^\s]*$#', $next) === 1) {
            $request->session()->put('social.next', $next);
        } else {
            $request->session()->forget('social.next');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider, SocialLoginService $socialLogin): RedirectResponse
    {
        // The reader pressed "Cancel" on the provider's consent screen.
        if ($request->filled('error')) {
            return $this->backToSignIn('social_cancelled');
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return $this->backToSignIn('social_failed');
        }

        $locale = $request->getPreferredLanguage(['el', 'en']) ?? config('app.locale');

        try {
            $user = $socialLogin->resolveUser($provider, $providerUser, $locale);
        } catch (UnverifiedSocialEmail) {
            return $this->backToSignIn('social_unverified');
        }
        $next = $request->session()->pull('social.next');

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->away(config('app.frontend_url').$this->landingPath($user, is_string($next) ? $next : null));
    }

    private function landingPath(User $user, ?string $next): string
    {
        if ($user->onboarded_at === null || $user->consent_at === null) {
            return '/onboarding';
        }

        return $next ?? '/';
    }

    private function backToSignIn(string $error): RedirectResponse
    {
        return redirect()->away(config('app.frontend_url').'/sign-in?error='.$error);
    }
}
