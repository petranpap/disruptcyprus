<?php

use App\Models\Industry;

it('lists published articles of a section, newest first, with cursor pagination', function () {
    $older = newArticle(['published_at' => now()->subDays(2)]);
    $newer = newArticle(['published_at' => now()->subHour()]);
    newArticle(['status' => 'draft', 'published_at' => null]);
    newArticle(['status' => 'scheduled', 'published_at' => now()->addDay()]);
    newArticle(['published_at' => now()->subHour()], section: 'startups');

    $this->getJson('/api/v1/sections/news/articles')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('data.0.type', 'article')
        ->assertJsonPath('data.0.section.slug', 'news')
        ->assertJsonStructure(['data' => [['title', 'excerpt', 'author', 'reads', 'image', 'locale', 'is_fallback', 'is_bookmarked', 'reading_time_minutes']], 'meta' => ['next_cursor']]);
});

it('paginates with a cursor without duplicates', function () {
    foreach (range(1, 25) as $hour) {
        newArticle(['published_at' => now()->subHours($hour)]);
    }

    $first = $this->getJson('/api/v1/sections/news/articles')->assertJsonCount(20, 'data');
    $second = $this->getJson('/api/v1/sections/news/articles?cursor='.$first->json('meta.next_cursor'))->assertJsonCount(5, 'data');

    expect(array_intersect(collect($first->json('data'))->pluck('id')->all(), collect($second->json('data'))->pluck('id')->all()))->toBe([])
        ->and($second->json('meta.next_cursor'))->toBeNull();
});

it('filters by industry slugs in both query styles', function () {
    $fintech = Industry::factory()->create(['slug' => 'fintech']);
    $ai = Industry::factory()->create(['slug' => 'ai']);
    $maritime = Industry::factory()->create(['slug' => 'maritime']);
    $fintechArticle = newArticle(industries: [$fintech]);
    $aiArticle = newArticle(industries: [$ai]);
    newArticle(industries: [$maritime]);

    $commaIds = collect($this->getJson('/api/v1/sections/news/articles?industry=fintech,ai')->json('data'))->pluck('id')->sort()->values()->all();
    $arrayIds = collect($this->getJson('/api/v1/sections/news/articles?industry[]=fintech&industry[]=ai')->json('data'))->pluck('id')->sort()->values()->all();

    expect($commaIds)->toBe(collect([$fintechArticle->id, $aiArticle->id])->sort()->values()->all())
        ->and($arrayIds)->toBe($commaIds);

    $this->getJson('/api/v1/sections/news/articles?industry=unknown')->assertUnprocessable();
});

it('respects the reader content languages and marks fallbacks', function () {
    $greekOnly = newArticle()->replaceTranslations('title', ['el' => 'Μόνο ελληνικά'])->replaceTranslations('body', ['el' => '<p>Κείμενο</p>']);
    $greekOnly->replaceTranslations('excerpt', ['el' => 'Περίληψη'])->save();
    $both = newArticle();

    $guestIds = collect($this->withHeader('Accept-Language', 'en')->getJson('/api/v1/sections/news/articles')->json('data'))
        ->mapWithKeys(fn ($item) => [$item['id'] => $item['is_fallback']]);
    expect($guestIds->all())->toEqual([$both->id => false, $greekOnly->id => true]);

    $englishReader = reader(['content_locales' => ['en']]);
    $this->actingAs($englishReader)->withHeader('Accept-Language', 'el')->getJson('/api/v1/sections/news/articles')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $both->id)
        ->assertJsonPath('data.0.locale', 'en');
});

it('does not list articles for the events section', function () {
    section('events');

    assertApiError($this->getJson('/api/v1/sections/events/articles'), 404, 'not_found');
});

it('caches publicly for guests and privately for readers, with ETags', function () {
    newArticle();

    $guest = $this->getJson('/api/v1/sections/news/articles')->assertOk();
    expect($guest->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=60');
    $this->withHeader('If-None-Match', $guest->headers->get('ETag'))->getJson('/api/v1/sections/news/articles')->assertStatus(304);
    $this->flushHeaders();

    $signedIn = $this->actingAs(reader())->getJson('/api/v1/sections/news/articles')->assertOk();
    expect($signedIn->headers->get('Cache-Control'))->toContain('private')->toContain('no-cache');
});
