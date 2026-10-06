<?php

it('requires authentication', function () {
    $this->getJson('/api/v1/bookmarks')->assertUnauthorized();
    $this->postJson('/api/v1/bookmarks', ['type' => 'article', 'id' => 1])->assertUnauthorized();
});

it('saves an item idempotently and reflects it on cards', function () {
    $user = reader();
    $article = newArticle(['slug' => 'saved-one']);

    $this->actingAs($user)->postJson('/api/v1/bookmarks', ['type' => 'article', 'id' => $article->id])
        ->assertCreated()
        ->assertJsonPath('data.is_bookmarked', true);
    $this->actingAs($user)->postJson('/api/v1/bookmarks', ['type' => 'article', 'id' => $article->id])->assertOk();

    expect($user->bookmarks()->count())->toBe(1);

    $this->actingAs($user)->getJson('/api/v1/articles/saved-one')->assertJsonPath('data.is_bookmarked', true);
    $this->actingAs(reader())->getJson('/api/v1/articles/saved-one')->assertJsonPath('data.is_bookmarked', false);
    $this->getJson('/api/v1/articles/saved-one')->assertJsonPath('data.is_bookmarked', false);
});

it('refuses unpublished or unknown items', function () {
    $user = reader();
    $draft = newArticle(['status' => 'draft', 'published_at' => null]);

    $this->actingAs($user)->postJson('/api/v1/bookmarks', ['type' => 'article', 'id' => $draft->id])->assertNotFound();
    $this->actingAs($user)->postJson('/api/v1/bookmarks', ['type' => 'article', 'id' => 999999])->assertNotFound();
    $this->actingAs($user)->postJson('/api/v1/bookmarks', ['type' => 'podcast', 'id' => 1])->assertUnprocessable();
});

it('lists saved items newest first, filtered by type, hiding unpublished ones', function () {
    $user = reader();
    $article = newArticle();
    $event = newEvent();
    $archived = newArticle();

    foreach ([$article, $archived, $event] as $item) {
        $this->actingAs($user)->postJson('/api/v1/bookmarks', ['type' => $item->getMorphClass(), 'id' => $item->id])->assertCreated();
    }
    $archived->update(['status' => 'archived']);

    $all = $this->actingAs($user)->getJson('/api/v1/bookmarks')->assertOk();
    expect(collect($all->json('data'))->map(fn ($item) => $item['type'].':'.$item['id'])->all())->toBe(["event:{$event->id}", "article:{$article->id}"])
        ->and($all->json('data.0'))->toHaveKey('bookmarked_at')
        ->and($all->json('data.0.is_bookmarked'))->toBeTrue();

    $this->actingAs($user)->getJson('/api/v1/bookmarks?type=article')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $article->id);
});

it('removes a saved item', function () {
    $user = reader();
    $event = newEvent();
    $user->bookmarks()->create(['bookmarkable_type' => 'event', 'bookmarkable_id' => $event->id]);

    $this->actingAs($user)->deleteJson('/api/v1/bookmarks', ['type' => 'event', 'id' => $event->id])->assertNoContent();

    expect($user->bookmarks()->count())->toBe(0);
});
