<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('changes the password after checking the current one', function () {
    $user = reader();

    $this->actingAs($user)->putJson('/api/v1/me/password', [
        'current_password' => 'wrong',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

    $this->actingAs($user)->putJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertOk();

    expect(Hash::check('newpass123', $user->refresh()->password))->toBeTrue();
});

it('lets social-only users set a first password', function () {
    $user = User::factory()->withoutPassword()->create();

    $this->actingAs($user)->putJson('/api/v1/me/password', [
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertOk();

    expect($user->refresh()->hasPassword())->toBeTrue();
});

it('revokes other API tokens but keeps the current one', function () {
    $user = reader();
    $current = $user->createToken('current')->plainTextToken;
    $user->createToken('other');

    $this->withToken($current)->putJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertOk();

    expect($user->tokens()->pluck('name')->all())->toBe(['current']);
});
