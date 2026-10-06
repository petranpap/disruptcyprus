<?php

use App\Models\Article;
use App\Models\Event;
use App\Models\Industry;
use App\Models\Section;
use App\Services\Feed\FeedCursor;
use App\Services\Feed\FeedService;
use Carbon\CarbonImmutable;

function rankedArticle(int $id, string $section, float $ageHours, bool $featured = false, bool $original = false, array $industryIds = [1]): Article
{
    $asOf = CarbonImmutable::parse('2026-10-07 12:00:00', 'UTC');
    $article = (new Article)->forceFill([
        'id' => $id,
        'published_at' => $asOf->subMinutes((int) round($ageHours * 60)),
        'is_featured' => $featured,
        'is_original' => $original,
    ]);
    $article->setRelation('section', (new Section)->forceFill(['slug' => $section]));
    $article->setRelation('industries', collect($industryIds)->map(fn ($industryId) => (new Industry)->forceFill(['id' => $industryId])));

    return $article;
}

function feed(): FeedService
{
    return app(FeedService::class);
}

it('halves an article score every 36 hours', function () {
    $asOf = CarbonImmutable::parse('2026-10-07 12:00:00', 'UTC');

    expect(feed()->score(rankedArticle(1, 'news', 0), [1], $asOf))->toEqualWithDelta(1.0, 0.001)
        ->and(feed()->score(rankedArticle(1, 'news', 36), [1], $asOf))->toEqualWithDelta(0.5, 0.001)
        ->and(feed()->score(rankedArticle(1, 'news', 72), [1], $asOf))->toEqualWithDelta(0.25, 0.001);
});

it('boosts featured, originals and extra matching industries', function () {
    $asOf = CarbonImmutable::parse('2026-10-07 12:00:00', 'UTC');

    expect(feed()->score(rankedArticle(1, 'news', 0, featured: true, original: true), [1], $asOf))->toEqualWithDelta(1.4, 0.001)
        ->and(feed()->score(rankedArticle(1, 'news', 0, industryIds: [1, 2, 3]), [1, 2, 3], $asOf))->toEqualWithDelta(1.2, 0.001)
        ->and(feed()->score(rankedArticle(1, 'news', 0, industryIds: [1, 2, 3, 4, 5, 6]), [1, 2, 3, 4, 5, 6], $asOf))->toEqualWithDelta(1.3, 0.001);
});

it('scores near events above distant ones', function () {
    $asOf = CarbonImmutable::parse('2026-10-07 12:00:00', 'UTC');
    $event = fn (int $hours) => tap((new Event)->forceFill(['starts_at' => $asOf->addHours($hours), 'is_featured' => false]), fn (Event $event) => $event->setRelation('industries', collect([(new Industry)->forceFill(['id' => 1])])));

    expect(feed()->score($event(0), [1], $asOf))->toEqualWithDelta(0.6, 0.001)
        ->and(feed()->score($event(96), [1], $asOf))->toEqualWithDelta(0.3, 0.001);
});

it('never shows more than three items of one section in a row when others are available', function () {
    $ranked = [
        rankedArticle(1, 'news', 1), rankedArticle(2, 'news', 2), rankedArticle(3, 'news', 3),
        rankedArticle(4, 'news', 4), rankedArticle(5, 'news', 5), rankedArticle(6, 'startups', 6),
        rankedArticle(7, 'research', 7),
    ];

    $order = array_map(fn (Article $article) => $article->id, feed()->diversify($ranked));

    expect($order)->toBe([1, 2, 3, 6, 4, 5, 7]);
});

it('keeps score order when only one section exists', function () {
    $ranked = array_map(fn ($id) => rankedArticle($id, 'news', $id), [1, 2, 3, 4, 5]);

    expect(array_map(fn (Article $article) => $article->id, feed()->diversify($ranked)))->toBe([1, 2, 3, 4, 5]);
});

it('round-trips cursors and rejects tampered or future ones', function () {
    $cursor = new FeedCursor(CarbonImmutable::now()->subHour()->startOfSecond(), 40);
    $decoded = FeedCursor::decode($cursor->encode());

    expect($decoded->offset)->toBe(40)
        ->and($decoded->asOf->getTimestamp())->toBe($cursor->asOf->getTimestamp())
        ->and(FeedCursor::decode('not-a-cursor')->offset)->toBe(0)
        ->and(FeedCursor::decode((new FeedCursor(CarbonImmutable::now()->addDay(), 20))->encode())->offset)->toBe(0);
});
