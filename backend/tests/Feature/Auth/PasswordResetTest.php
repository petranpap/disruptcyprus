<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('answers identically whether or not the account exists', function () {
    Notification::fake();
    $user = reader(['email' => 'reader@example.com']);

    $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'reader@example.com'])->assertOk();
    $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

    expect($known->json('message'))->toBe($unknown->json('message'));
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $url = call_user_func(ResetPassword::$createUrlCallback, $user, $notification->token);

        return str_starts_with($url, config('app.frontend_url').'/reset-password?token=');
    });
});

it('resets the password with a valid token and revokes API tokens', function () {
    $user = reader(['email' => 'reader@example.com']);
    $user->createToken('old-device');
    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'reader@example.com',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertOk();

    expect(Hash::check('newpass123', $user->refresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
});

it('rejects an invalid reset token', function () {
    reader(['email' => 'reader@example.com']);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid',
        'email' => 'reader@example.com',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});
