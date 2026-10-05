<?php

use App\Models\Article;
use App\Models\Industry;
use App\Models\User;

it('deletes the account after password confirmation and removes personal data', function () {
    $user = reader(['email' => 'reader@example.com', 'name' => 'Real Name']);
    $user->industries()->attach(Industry::factory()->create(), ['notify' => true]);
    $user->bookmarks()->create(['bookmarkable_type' => 'article', 'bookmarkable_id' => Article::factory()->create()->id]);
    $user->socialAccounts()->create(['provider' => 'google', 'provider_id' => 'g-1']);
    $user->createToken('phone');
    $user->preferences();

    $this->actingAs($user, 'web')->withHeaders(spaHeaders())
        ->deleteJson('/api/v1/me', ['password' => 'password'])
        ->assertNoContent();

    $deleted = User::withTrashed()->findOrFail($user->id);

    expect($deleted->trashed())->toBeTrue()
        ->and($deleted->email)->not->toBe('reader@example.com')
        ->and($deleted->name)->toBe('Deleted user')
        ->and($deleted->password)->toBeNull()
        ->and($deleted->industries()->count())->toBe(0)
        ->and($deleted->bookmarks()->count())->toBe(0)
        ->and($deleted->socialAccounts()->count())->toBe(0)
        ->and($deleted->tokens()->count())->toBe(0)
        ->and($deleted->notificationPreference()->exists())->toBeFalse();

    $this->assertGuest('web');

    // The address can be used again for a new account.
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Again', 'email' => 'reader@example.com', 'password' => 'secret123', 'consent' => true,
    ])->assertCreated();
});

it('requires the password', function () {
    $this->actingAs(reader())->deleteJson('/api/v1/me', ['password' => 'wrong'])
        ->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('requires the confirmation word for social-only accounts', function () {
    $user = User::factory()->withoutPassword()->create();

    $this->actingAs($user)->deleteJson('/api/v1/me', ['confirmation' => 'nope'])
        ->assertUnprocessable()->assertJsonValidationErrors('confirmation');

    $this->actingAs($user)->deleteJson('/api/v1/me', ['confirmation' => 'DELETE'])->assertNoContent();

    expect(User::withTrashed()->find($user->id)?->trashed())->toBeTrue();
});
