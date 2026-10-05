<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('requires authentication', function () {
    assertApiError($this->getJson('/api/v1/me'), 401, 'unauthenticated');
});

it('returns the current user over the SPA session', function () {
    $user = reader();

    $this->actingAs($user, 'web')->withHeaders(spaHeaders())->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.has_password', true)
        ->assertJsonMissingPath('data.password');
});

it('updates name, locales and timezone', function () {
    $user = reader();

    $this->actingAs($user)->patchJson('/api/v1/me', [
        'name' => 'New Name',
        'locale' => 'en',
        'content_locales' => ['en'],
        'timezone' => 'Europe/Athens',
    ])->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.content_locales', ['en'])
        ->assertJsonPath('data.timezone', 'Europe/Athens');
});

it('validates locales and timezone', function () {
    $this->actingAs(reader())->patchJson('/api/v1/me', [
        'locale' => 'fr',
        'content_locales' => [],
        'timezone' => 'Mars/Olympus',
    ])->assertUnprocessable()->assertJsonValidationErrors(['locale', 'content_locales', 'timezone']);
});

it('records consent and onboarding completion', function () {
    $user = User::factory()->withoutPassword()->notOnboarded()->create(['consent_at' => null]);

    $this->actingAs($user)->patchJson('/api/v1/me', ['consent' => true, 'onboarding_completed' => true])
        ->assertOk()
        ->assertJsonPath('data.needs_consent', false)
        ->assertJsonPath('data.onboarded', true);
});

it('requires the current password to change the email and re-verifies it', function () {
    Notification::fake();
    $user = reader(['email' => 'old@example.com']);

    $this->actingAs($user)->patchJson('/api/v1/me', ['email' => 'new@example.com'])
        ->assertUnprocessable()->assertJsonValidationErrors('current_password');

    $this->actingAs($user)->patchJson('/api/v1/me', ['email' => 'new@example.com', 'current_password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.email', 'new@example.com')
        ->assertJsonPath('data.email_verified', false);

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('lets social-only users change email without a password', function () {
    Notification::fake();
    $user = User::factory()->withoutPassword()->create();

    $this->actingAs($user)->patchJson('/api/v1/me', ['email' => 'new@example.com'])->assertOk();
});
