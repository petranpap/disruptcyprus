<?php

namespace App\Services\Account;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as ProviderUser;

/**
 * Resolves the local account for an OAuth identity: existing link, then verified email match, then a new account.
 */
class SocialLoginService
{
    public function resolveUser(string $provider, ProviderUser $providerUser, string $locale): User
    {
        $linked = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', (string) $providerUser->getId())
            ->first();

        if ($linked !== null) {
            return $linked->user()->firstOrFail();
        }

        $email = mb_strtolower((string) $providerUser->getEmail());

        return DB::transaction(function () use ($provider, $providerUser, $email, $locale): User {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                // Consent is collected in onboarding; needs_consent stays true until then.
                $user = User::query()->create([
                    'name' => $providerUser->getName() ?: strstr($email, '@', true),
                    'email' => $email,
                    'password' => null,
                    'locale' => $locale,
                    'email_verified_at' => now(),
                ]);
            } elseif ($user->email_verified_at === null) {
                // The provider has verified ownership of this address.
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => (string) $providerUser->getId(),
            ]);

            return $user;
        });
    }
}
