<?php

use App\Models\Industry;
use Illuminate\Support\Facades\DB;

it('requires authentication for the personalized feed', function () {
    assertApiError($this->getJson('/api/v1/feed/for-you'), 401, 'unauthenticated');
});

it('builds the for-you feed from followed industries, recent articles and near events', function () {
    [$followed, $other] = Industry::factory()->count(2)->create();
    $user = reader(['content_locales' => ['el', 'en']]);
    $user->industries()->attach($followed);

    $recent = newArticle(['published_at' => now()->subHours(3)], [$followed]);
    $multi = newArticle(['published_at' => now()->subHours(5)], [$other, $followed]);
    newArticle(['published_at' => now()->subHours(1)], [$other]);
    newArticle(['published_at' => now()->subDays(40)], [$followed]);
    newArticle(['status' => 'draft', 'published_at' => null], [$followed]);
    $soon = newEvent(['starts_at' => now()->addDays(3)], [$followed]);
    newEvent(['starts_at' => now()->addDays(20)], [$followed]);
    newEvent(['starts_at' => now()->subDay()], [$followed]);

    $response = $this->actingAs($user)->getJson('/api/v1/feed/for-you')->assertOk()->assertJsonPath('meta.fallback', null);

    $items = collect($response->json('data'))->map(fn ($item) => $item['type'].':'.$item['id'])->sort()->values()->all();

    expect($items)->toBe(collect(["article:{$recent->id}", "article:{$multi->id}", "event:{$soon->id}"])->sort()->values()->all());
});

it('ranks featured and original stories above equally fresh ones', function () {
    $industry = Industry::factory()->create();
    $user = reader();
    $user->industries()->attach($industry);

    $plain = newArticle(['published_at' => now()->subHours(2)], [$industry]);
    $featured = newArticle(['published_at' => now()->subHours(2), 'is_featured' => true], [$industry]);
    $older = newArticle(['published_at' => now()->subHours(30)], [$industry]);

    $ids = collect($this->actingAs($user)->getJson('/api/v1/feed/for-you')->json('data'))->pluck('id')->all();

    expect($ids)->toBe([$featured->id, $plain->id, $older->id]);
});

it('keeps pages stable while new content is published', function () {
    $industry = Industry::factory()->create();
    $user = reader();
    $user->industries()->attach($industry);

    foreach (range(1, 25) as $hour) {
        newArticle(['published_at' => now()->subHours($hour)], [$industry]);
    }

    $first = $this->actingAs($user)->getJson('/api/v1/feed/for-you')->assertJsonCount(20, 'data');

    $this->travel(5)->minutes();
    $breaking = newArticle(['published_at' => now()], [$industry]);

    $second = $this->actingAs($user)->getJson('/api/v1/feed/for-you?cursor='.$first->json('meta.next_cursor'))->assertJsonCount(5, 'data');

    $all = [...collect($first->json('data'))->pluck('id'), ...collect($second->json('data'))->pluck('id')];
    expect($all)->toHaveCount(25)->and(array_unique($all))->toHaveCount(25)->and($all)->not->toContain($breaking->id)
        ->and($second->json('meta.next_cursor'))->toBeNull();
});

it('restarts the feed when the cursor is invalid', function () {
    $industry = Industry::factory()->create();
    $user = reader();
    $user->industries()->attach($industry);
    newArticle([], [$industry]);

    $this->actingAs($user)->getJson('/api/v1/feed/for-you?cursor=garbage')->assertOk()->assertJsonCount(1, 'data');
});

it('falls back to trending for readers without industries', function () {
    newArticle();

    $this->actingAs(reader())->getJson('/api/v1/feed/for-you')
        ->assertOk()
        ->assertJsonPath('meta.fallback', 'trending')
        ->assertJsonCount(1, 'data');
});

it('ranks trending by views plus three times saves over the last 48 hours', function () {
    $viewed = newArticle(['view_count' => 100]);
    $saved = newArticle(['view_count' => 30]);
    $stale = newArticle(['view_count' => 0, 'published_at' => now()->subDays(3)]);

    $bucket = fn ($article, $views, $hoursAgo) => DB::table('content_view_stats')->insert([
        'viewable_type' => 'article', 'viewable_id' => $article->id, 'bucket_at' => now()->subHours($hoursAgo)->startOfHour(), 'views' => $views,
    ]);
    $bucket($viewed, 100, 10);
    $bucket($saved, 30, 5);
    $bucket($stale, 1000, 60);

    foreach (range(1, 30) as $index) {
        reader()->bookmarks()->create(['bookmarkable_type' => 'article', 'bookmarkable_id' => $saved->id]);
    }

    $this->getJson('/api/v1/feed/trending')
        ->assertOk()
        ->assertJsonPath('data.0.id', $saved->id)
        ->assertJsonPath('data.1.id', $viewed->id)
        ->assertJsonPath('data.2.id', $stale->id)
        ->assertJsonPath('data.1.reads', 100);
});

it('weaves upcoming events into the first page of an industry feed', function () {
    $industry = Industry::factory()->create(['slug' => 'fintech']);
    foreach (range(1, 5) as $hour) {
        newArticle(['published_at' => now()->subHours($hour)], [$industry]);
    }
    $event = newEvent(['starts_at' => now()->addDays(2)], [$industry]);
    newEvent(['starts_at' => now()->subDays(2)], [$industry]);

    $response = $this->getJson('/api/v1/industries/fintech/feed')->assertOk()->assertJsonPath('meta.industry.slug', 'fintech');

    expect(collect($response->json('data'))->pluck('type')->all())->toBe(['article', 'event', 'article', 'article', 'article', 'article'])
        ->and($response->json('data.1.id'))->toBe($event->id);

    Industry::query()->whereKey($industry->id)->update(['is_active' => false]);
    $this->getJson('/api/v1/industries/fintech/feed')->assertNotFound();
});
