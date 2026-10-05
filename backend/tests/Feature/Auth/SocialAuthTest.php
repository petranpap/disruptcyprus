<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(string $id = 'google-123', string $email = 'anna@gmail.com'): void
{
    $providerUser = (new SocialiteUser)->map(['id' => $id, 'name' => 'Anna K', 'email' => $email]);

    Socialite::shouldReceive('driver->user')->andReturn($providerUser);
}

it('redirects to Google', function () {
    Socialite::shouldReceive('driver->redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    $this->get('/api/v1/auth/social/google/redirect')->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

it('rejects unsupported providers', function () {
    $this->get('/api/v1/auth/social/facebook/redirect')->assertNotFound();
});

it('creates a new verified account without consent and sends it to onboarding', function () {
    fakeGoogleUser();

    $this->get('/api/v1/auth/social/google/callback')->assertRedirect(config('app.frontend_url').'/onboarding');

    $user = User::query()->where('email', 'anna@gmail.com')->firstOrFail();
    expect($user->password)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->consent_at)->toBeNull()
        ->and($user->socialAccounts()->where('provider', 'google')->exists())->toBeTrue();

    $this->assertAuthenticatedAs($user, 'web');
});

it('links Google to an existing account with the same email', function () {
    $existing = reader(['email' => 'anna@gmail.com']);
    fakeGoogleUser();

    $this->get('/api/v1/auth/social/google/callback')->assertRedirect(config('app.frontend_url').'/');

    expect(User::query()->count())->toBe(1)
        ->and($existing->socialAccounts()->count())->toBe(1);
    $this->assertAuthenticatedAs($existing, 'web');
});

it('signs in an already linked account even if the Google email changed', function () {
    $existing = reader(['email' => 'old@example.com']);
    $existing->socialAccounts()->create(['provider' => 'google', 'provider_id' => 'google-123']);
    fakeGoogleUser('google-123', 'new@gmail.com');

    $this->get('/api/v1/auth/social/google/callback');

    $this->assertAuthenticatedAs($existing, 'web');
});

it('redirects back to sign-in when the provider fails', function () {
    Socialite::shouldReceive('driver->user')->andThrow(new RuntimeException('state mismatch'));

    $this->get('/api/v1/auth/social/google/callback')
        ->assertRedirect(config('app.frontend_url').'/sign-in?error=social_failed');
    $this->assertGuest('web');
});
