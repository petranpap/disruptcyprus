<?php

use App\Models\Industry;
use Database\Seeders\IndustrySeeder;

beforeEach(function () {
    $this->seed(IndustrySeeder::class);
});

it('matches Greek words regardless of accents and case, and word prefixes', function () {
    $article = newArticle(['title' => ['el' => 'Η Κύπρος ως κόμβος καινοτομίας', 'en' => 'Cyprus as an innovation hub']]);
    newArticle(['title' => ['el' => 'Άσχετο θέμα', 'en' => 'Unrelated topic']]);

    foreach (['κυπρος', 'ΚΥΠΡΟΣ', 'καινοτ', 'innovat'] as $query) {
        $this->getJson('/api/v1/search?q='.urlencode($query))
            ->assertOk()
            ->assertJsonCount(1, 'data.articles')
            ->assertJsonPath('data.articles.0.id', $article->id);
    }
});

it('requires every word to match', function () {
    newArticle(['title' => ['en' => 'Shipping startup raises seed', 'el' => 'Ναυτιλιακή startup αντλεί κεφάλαια']]);

    $this->getJson('/api/v1/search?q=shipping%20seed')->assertJsonCount(1, 'data.articles');
    $this->getJson('/api/v1/search?q=shipping%20quantum')->assertJsonCount(0, 'data.articles');
});

it('matches short terms like AI as whole words', function () {
    $ai = newArticle(['title' => ['en' => 'AI lab opens in Nicosia', 'el' => 'Εργαστήριο AI στη Λευκωσία']]);
    newArticle(['title' => ['en' => 'Blockchain said to maintain growth', 'el' => 'Blockchain']]);

    $response = $this->getJson('/api/v1/search?q=ai')->assertOk();

    expect(collect($response->json('data.articles'))->pluck('id')->all())->toBe([$ai->id])
        ->and(collect($response->json('data.industries'))->pluck('slug')->all())->toBe(['artificial-intelligence']);
});

it('finds industries by Greek name without accents', function () {
    $this->withHeader('Accept-Language', 'el')->getJson('/api/v1/search?q=ναυτιλια')
        ->assertJsonPath('data.industries.0.slug', 'maritime')
        ->assertJsonPath('data.industries.0.name', 'Ναυτιλία');
});

it('searches events, upcoming first', function () {
    $past = newEvent(['title' => ['en' => 'Fintech meetup archive', 'el' => 'Fintech meetup'], 'starts_at' => now()->subDays(2)]);
    $upcoming = newEvent(['title' => ['en' => 'Fintech meetup next', 'el' => 'Fintech meetup'], 'starts_at' => now()->addDays(10)]);

    $response = $this->getJson('/api/v1/search?q=fintech%20meetup')->assertOk();

    expect(collect($response->json('data.events'))->pluck('id')->all())->toBe([$upcoming->id, $past->id]);
});

it('only returns content in the reader languages and published content', function () {
    $greekOnly = newArticle(['title' => ['el' => 'Δοκιμή αναζήτησης'], 'excerpt' => ['el' => 'x'], 'body' => ['el' => '<p>Δοκιμή</p>']]);
    newArticle(['title' => ['el' => 'Δοκιμή πρόχειρο', 'en' => 'Draft'], 'status' => 'draft', 'published_at' => null]);

    $this->getJson('/api/v1/search?q=δοκιμη')->assertJsonCount(1, 'data.articles')->assertJsonPath('data.articles.0.id', $greekOnly->id);
    $this->actingAs(reader(['content_locales' => ['en']]))->getJson('/api/v1/search?q=δοκιμη')->assertJsonCount(0, 'data.articles');
});

it('validates the query', function () {
    $this->getJson('/api/v1/search')->assertUnprocessable()->assertJsonValidationErrors('q');
    $this->getJson('/api/v1/search?q=a')->assertUnprocessable();
    $this->getJson('/api/v1/search?q=%2B%2B')->assertOk()->assertJsonCount(0, 'data.articles');
});

it('keeps industries in sync with the seeder count', function () {
    expect(Industry::query()->count())->toBe(33);
});
