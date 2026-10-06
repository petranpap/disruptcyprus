<?php

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Enums\DigestStatus;
use App\Models\Digest;
use App\Models\Industry;

function digestWith(array $attributes, array $items): Digest
{
    $digest = Digest::factory()->create($attributes);

    foreach ($items as $position => $item) {
        $digest->items()->create([
            'itemable_type' => $item->getMorphClass(),
            'itemable_id' => $item->getKey(),
            'position' => $position + 1,
            'editor_note' => ['en' => 'Note '.($position + 1), 'el' => 'Σημείωση '.($position + 1)],
        ]);
    }

    return $digest;
}

it('lists published digests filtered by kind and cadence', function () {
    $daily = digestWith(['period_start' => '2026-10-04', 'period_end' => '2026-10-04', 'slug' => 'daily-news-2026-10-04'], [newArticle()]);
    digestWith(['period_start' => '2026-10-05', 'period_end' => '2026-10-05', 'slug' => 'daily-news-2026-10-05', 'status' => DigestStatus::Draft, 'published_at' => null], []);
    $weekly = digestWith(['kind' => DigestKind::Events, 'cadence' => DigestCadence::Weekly, 'period_start' => '2026-10-05', 'period_end' => '2026-10-11', 'slug' => 'weekly-events-2026-W41'], [newEvent()]);

    $this->getJson('/api/v1/digests')->assertOk()->assertJsonCount(2, 'data');

    $this->getJson('/api/v1/digests?kind=news&cadence=daily')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $daily->slug)
        ->assertJsonPath('data.0.items_count', 1)
        ->assertJsonPath('data.0.share_url', config('app.url').'/d/'.$daily->slug);

    $this->getJson('/api/v1/digests?kind=events')->assertJsonPath('data.0.slug', $weekly->slug);
    $this->getJson('/api/v1/digests?kind=podcasts')->assertUnprocessable();
});

it('returns the latest published digest of a kind and cadence', function () {
    digestWith(['period_start' => '2026-10-03', 'period_end' => '2026-10-03', 'slug' => 'older'], [newArticle()]);
    digestWith(['period_start' => '2026-10-04', 'period_end' => '2026-10-04', 'slug' => 'newer'], [newArticle()]);

    $this->getJson('/api/v1/digests/latest?kind=news&cadence=daily')->assertOk()->assertJsonPath('data.slug', 'newer');
    $this->getJson('/api/v1/digests/latest?kind=news&cadence=monthly')->assertNotFound();
    $this->getJson('/api/v1/digests/latest?kind=news')->assertUnprocessable()->assertJsonValidationErrors('cadence');
});

it('shows items in editor order, hides unpublished ones and highlights followed industries', function () {
    $followed = Industry::factory()->create();
    $first = newArticle([], [Industry::factory()->create()]);
    $removed = newArticle();
    $second = newArticle([], [$followed]);
    $digest = digestWith(['slug' => 'daily'], [$first, $removed, $second]);
    $removed->update(['status' => 'archived']);

    $user = reader();
    $user->industries()->attach($followed);

    $response = $this->actingAs($user)->withHeader('Accept-Language', 'en')->getJson('/api/v1/digests/daily')->assertOk();

    expect(collect($response->json('data.items'))->map(fn ($item) => [$item['item']['id'], $item['is_highlighted']])->all())
        ->toBe([[$first->id, false], [$second->id, true]])
        ->and($response->json('data.items.0.editor_note'))->toBe('Note 1')
        ->and($response->json('data.items_count'))->toBe(2);
});

it('does not expose draft digests', function () {
    digestWith(['slug' => 'draft', 'status' => DigestStatus::Draft, 'published_at' => null], []);

    $this->getJson('/api/v1/digests/draft')->assertNotFound();
});
