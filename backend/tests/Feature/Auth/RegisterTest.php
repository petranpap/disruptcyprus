<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('registers a user, records consent and signs them in for SPA requests', function () {
    $response = $this->withHeaders(spaHeaders())->postJson('/api/v1/auth/register', [
        'name' => 'Anna Kyriakou',
        'email' => 'Anna@Example.com ',
        'password' => 'secret123',
        'consent' => true,
        'locale' => 'en',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'anna@example.com')
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.content_locales', ['el', 'en'])
        ->assertJsonPath('data.needs_consent', false)
        ->assertJsonPath('data.onboarded', false)
        ->assertJsonPath('data.email_verified', false);

    $user = User::query()->where('email', 'anna@example.com')->firstOrFail();
    expect($user->consent_at)->not->toBeNull()
        ->and($user->consent_version)->toBe(config('app.consent_version'));

    $this->assertAuthenticatedAs($user, 'web');
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('defaults the locale to Greek', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Nikos', 'email' => 'nikos@example.com', 'password' => 'secret123', 'consent' => true,
    ])->assertCreated()->assertJsonPath('data.locale', 'el')->assertJsonPath('data.timezone', 'Asia/Nicosia');
});

it('requires consent', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Nikos', 'email' => 'nikos@example.com', 'password' => 'secret123',
    ]);

    assertApiError($response, 422, 'validation_failed')->assertJsonValidationErrors('consent');
});

it('rejects duplicate emails case-insensitively', function () {
    reader(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'X', 'email' => 'TAKEN@example.com', 'password' => 'secret123', 'consent' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('enforces the password policy', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'X', 'email' => 'x@example.com', 'password' => 'short', 'consent' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('returns Greek validation messages for Greek and unsupported languages, English on request', function () {
    $this->withHeader('Accept-Language', 'el')->postJson('/api/v1/auth/register', [])
        ->assertJsonPath('errors.name.0', 'Το πεδίο όνομα είναι υποχρεωτικό.');

    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')->postJson('/api/v1/auth/register', [])
        ->assertJsonPath('errors.name.0', 'Το πεδίο όνομα είναι υποχρεωτικό.');

    $this->withHeader('Accept-Language', 'en-GB,en;q=0.9')->postJson('/api/v1/auth/register', [])
        ->assertJsonPath('errors.name.0', 'The name field is required.');
});
