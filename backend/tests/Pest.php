<?php

use App\Models\Article;
use App\Models\Event;
use App\Models\Industry;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->use(DatabaseTruncation::class)->in('Search');
pest()->extend(TestCase::class)->in('Unit');

/**
 * Headers that make Sanctum treat the request as coming from the PWA (cookie/session auth).
 *
 * @return array<string, string>
 */
function spaHeaders(): array
{
    return ['Origin' => 'http://localhost:5173', 'Referer' => 'http://localhost:5173/'];
}

/**
 * Asserts the shared API error envelope {message, code, errors?}.
 */
function assertApiError(TestResponse $response, int $status, string $code): TestResponse
{
    return $response->assertStatus($status)->assertJsonStructure(['message', 'code'])->assertJsonPath('code', $code);
}

function reader(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function section(string $slug = 'news'): Section
{
    return Section::query()->firstOrCreate(['slug' => $slug], [
        'name' => ['en' => ucfirst($slug), 'el' => ucfirst($slug).' ΕΛ'],
        'has_articles' => $slug !== 'events',
        'sort_order' => 1,
    ]);
}

/**
 * Published article (unless overridden) attached to the given industries; the first is primary.
 *
 * @param  array<string, mixed>  $attributes
 * @param  list<Industry>  $industries
 */
function newArticle(array $attributes = [], array $industries = [], string $section = 'news'): Article
{
    $article = Article::factory()->for(section($section))->create($attributes);

    foreach ($industries as $index => $industry) {
        $article->industries()->attach($industry, ['is_primary' => $index === 0]);
    }

    return $article->refresh();
}

/**
 * @param  array<string, mixed>  $attributes
 * @param  list<Industry>  $industries
 */
function newEvent(array $attributes = [], array $industries = []): Event
{
    $event = Event::factory()->create($attributes);

    foreach ($industries as $index => $industry) {
        $event->industries()->attach($industry, ['is_primary' => $index === 0]);
    }

    return $event->refresh();
}
