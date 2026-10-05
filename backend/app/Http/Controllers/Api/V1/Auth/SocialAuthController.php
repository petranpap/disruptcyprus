<?php

namespace App\Http\Controllers\Api\V1\Auth;

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

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider, SocialLoginService $socialLogin): RedirectResponse
    {
        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->away(config('app.frontend_url').'/sign-in?error=social_failed');
        }

        $locale = $request->getPreferredLanguage(['el', 'en']) ?? config('app.locale');
        $user = $socialLogin->resolveUser($provider, $providerUser, $locale);

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->away(config('app.frontend_url').$this->landingPath($user));
    }

    private function landingPath(User $user): string
    {
        return $user->onboarded_at === null || $user->consent_at === null ? '/onboarding' : '/';
    }
}
