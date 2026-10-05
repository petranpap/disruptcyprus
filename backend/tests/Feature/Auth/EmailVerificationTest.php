<?php

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('verifies the email through the signed link and redirects into the PWA', function () {
    $user = reader(['email_verified_at' => null]);

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertRedirect(config('app.frontend_url').'/?email_verified=1');

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects tampered verification links', function () {
    $user = reader(['email_verified_at' => null]);

    $this->getJson("/api/v1/auth/email/verify/{$user->id}/".sha1($user->email))->assertForbidden();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.com')]);
    $this->getJson($url)->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email only to unverified users', function () {
    Notification::fake();
    $unverified = reader(['email_verified_at' => null]);
    $verified = reader();

    $this->actingAs($unverified)->postJson('/api/v1/auth/email/verification-notification')->assertAccepted();
    $this->actingAs($verified)->postJson('/api/v1/auth/email/verification-notification')->assertAccepted();

    Notification::assertSentTo($unverified, VerifyEmail::class);
    Notification::assertNotSentTo($verified, VerifyEmail::class);
});
