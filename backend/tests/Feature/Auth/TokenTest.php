<?php

use App\Models\User;

it('issues a personal access token for native clients', function () {
    $user = reader(['email' => 'reader@example.com']);

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => 'reader@example.com', 'password' => 'password', 'device_name' => 'iPhone',
    ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email']]);

    expect($user->tokens()->count())->toBe(1);

    $this->withToken($response->json('token'))->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $user->id);
});

it('does not issue tokens for social-only accounts or wrong passwords', function () {
    User::factory()->withoutPassword()->create(['email' => 'social@example.com']);
    reader(['email' => 'reader@example.com']);

    $this->postJson('/api/v1/auth/token', ['email' => 'social@example.com', 'password' => 'anything', 'device_name' => 'x'])
        ->assertUnprocessable();
    $this->postJson('/api/v1/auth/token', ['email' => 'reader@example.com', 'password' => 'wrong', 'device_name' => 'x'])
        ->assertUnprocessable();
});

it('revokes the current token', function () {
    $user = reader();
    $plain = $user->createToken('phone')->plainTextToken;

    $this->withToken($plain)->deleteJson('/api/v1/auth/token')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
