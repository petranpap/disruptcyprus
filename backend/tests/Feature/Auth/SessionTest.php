<?php

use Illuminate\Support\Facades\RateLimiter;

it('signs in with valid credentials over the SPA session', function () {
    $user = reader(['email' => 'reader@example.com']);

    $this->withHeaders(spaHeaders())
        ->postJson('/api/v1/auth/login', ['email' => 'Reader@Example.com', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);

    $this->assertAuthenticatedAs($user, 'web');
});

it('rejects wrong credentials with a validation error', function () {
    reader(['email' => 'reader@example.com']);

    $response = $this->withHeaders(spaHeaders())
        ->postJson('/api/v1/auth/login', ['email' => 'reader@example.com', 'password' => 'wrong-password']);

    assertApiError($response, 422, 'validation_failed')->assertJsonValidationErrors('email');
    $this->assertGuest('web');
});

it('refuses cookie login for requests without a session', function () {
    reader(['email' => 'reader@example.com']);

    assertApiError(
        $this->postJson('/api/v1/auth/login', ['email' => 'reader@example.com', 'password' => 'password']),
        400,
        'http_error',
    );
});

it('throttles repeated login attempts', function () {
    RateLimiter::clear('login');
    reader(['email' => 'reader@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->withHeaders(spaHeaders())->postJson('/api/v1/auth/login', ['email' => 'reader@example.com', 'password' => 'nope']);
    }

    assertApiError(
        $this->withHeaders(spaHeaders())->postJson('/api/v1/auth/login', ['email' => 'reader@example.com', 'password' => 'password']),
        429,
        'too_many_requests',
    );
});

it('signs out', function () {
    $user = reader();

    $this->actingAs($user, 'web')->withHeaders(spaHeaders())
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    $this->assertGuest('web');
});

it('requires authentication to sign out', function () {
    assertApiError($this->postJson('/api/v1/auth/logout'), 401, 'unauthenticated');
});
