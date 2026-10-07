<?php

use App\Models\Industry;
use Database\Seeders\IndustrySeeder;
use Database\Seeders\SectionSeeder;

beforeEach(function () {
    $this->seed([SectionSeeder::class, IndustrySeeder::class]);
});

it('lists the five sections in order, localized by Accept-Language', function () {
    $this->withHeader('Accept-Language', 'el-GR,el;q=0.9,en;q=0.8')->getJson('/api/v1/sections')
        ->assertOk()
        ->assertHeader('Content-Language', 'el')
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('data.0.slug', 'news')
        ->assertJsonPath('data.0.name', 'Ειδήσεις')
        ->assertJsonPath('data.4.has_articles', false);

    $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/sections')
        ->assertJsonPath('data.0.name', 'News');
});

it('lists the 33 active industries with groups and colors', function () {
    Industry::query()->where('slug', 'space')->update(['is_active' => false]);
    cache()->flush();

    $response = $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/industries')->assertOk();

    $response->assertJsonCount(32, 'data')
        ->assertJsonPath('data.0.slug', 'fintech')
        ->assertJsonStructure(['data' => [['id', 'slug', 'name', 'group', 'color', 'image_url', 'sort_order']]]);

    expect(collect($response->json('data'))->pluck('slug'))->not->toContain('space');
});

it('sends cache headers and honours ETags on public taxonomy', function () {
    $first = $this->getJson('/api/v1/industries')->assertOk();

    expect($first->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=300')
        ->and($first->headers->get('Vary'))->toContain('Accept-Language');

    $this->withHeader('If-None-Match', $first->headers->get('ETag'))->getJson('/api/v1/industries')->assertStatus(304);
});

it('serves taxonomy from a serializing cache store, per language', function () {
    // Regression: Laravel 13 refuses to unserialize cached objects; only arrays may be cached.
    config(['cache.default' => 'file']);
    cache()->store('file')->flush();

    $first = $this->withHeader('Accept-Language', 'el')->getJson('/api/v1/industries')->assertOk();
    $cached = $this->withHeader('Accept-Language', 'el')->getJson('/api/v1/industries')->assertOk();
    $english = $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/sections')->assertOk();
    $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/sections')->assertOk()->assertJsonPath('data.0.name', 'News');

    expect($cached->json())->toBe($first->json())->and($english->json('data.0.name'))->toBe('News');

    Industry::query()->where('slug', 'fintech')->firstOrFail()->update(['name' => ['el' => 'Χρηματοτεχνολογία', 'en' => 'FinTech']]);
    $this->withHeader('Accept-Language', 'el')->getJson('/api/v1/industries')->assertJsonPath('data.0.name', 'Χρηματοτεχνολογία');

    cache()->store('file')->flush();
});

it('seeds industries idempotently', function () {
    $this->seed(IndustrySeeder::class);

    expect(Industry::query()->count())->toBe(33);
});
