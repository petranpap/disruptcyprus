<?php

namespace App\Services\Account;

use App\Exceptions\UnverifiedSocialEmail;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\AbstractUser;
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

            if ($user !== null && ! $this->emailVerified($providerUser)) {
                throw new UnverifiedSocialEmail;
            }

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

    /**
     * Google sends `email_verified` (OpenID Connect); providers that omit it are treated as verified.
     */
    private function emailVerified(ProviderUser $providerUser): bool
    {
        $raw = $providerUser instanceof AbstractUser ? $providerUser->getRaw() : [];
        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? true;

        return filter_var($verified, FILTER_VALIDATE_BOOLEAN);
    }
}
