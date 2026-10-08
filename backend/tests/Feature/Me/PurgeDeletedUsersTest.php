<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use App\Services\Account\AccountDeletionService;

it('permanently removes accounts deleted more than 30 days ago and keeps recent ones', function () {
    $old = reader();
    $recent = reader();
    $active = reader();
    app(AccountDeletionService::class)->delete($old);
    app(AccountDeletionService::class)->delete($recent);
    User::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(31)]);
    User::withTrashed()->whereKey($recent->id)->update(['deleted_at' => now()->subDays(29)]);

    $this->artisan('users:purge-deleted')->expectsOutput('Purged 1 deleted account(s).')->assertSuccessful();

    expect(User::withTrashed()->find($old->id))->toBeNull()
        ->and(User::withTrashed()->find($recent->id))->not->toBeNull()
        ->and(User::find($active->id))->not->toBeNull();
});

it('keeps articles by an author whose linked staff account is purged', function () {
    $editor = User::factory()->editor()->create();
    $author = Author::factory()->create(['user_id' => $editor->id]);
    $article = Article::factory()->for(section())->for($author)->create();
    app(AccountDeletionService::class)->delete($editor);
    User::withTrashed()->whereKey($editor->id)->update(['deleted_at' => now()->subDays(40)]);

    $this->artisan('users:purge-deleted')->assertSuccessful();

    expect($author->refresh()->user_id)->toBeNull()
        ->and(Article::find($article->id))->not->toBeNull();
});

it('runs every night', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('users:purge-deleted')->assertSuccessful();
});
