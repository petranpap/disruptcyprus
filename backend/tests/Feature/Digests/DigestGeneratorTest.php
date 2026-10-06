<?php

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Enums\DigestStatus;
use App\Models\Digest;
use App\Services\Digests\DigestGenerator;
use App\Services\Digests\GenerationMode;
use App\Services\Digests\GenerationOutcome;
use Carbon\CarbonImmutable;

function nicosia(string $time): CarbonImmutable
{
    return CarbonImmutable::parse($time, 'Asia/Nicosia');
}

function generator(): DigestGenerator
{
    return app(DigestGenerator::class);
}

it('selects daily news from 06:00 to 06:00 Cyprus time, featured first then most read', function () {
    $this->travelTo(nicosia('2026-10-07 06:00'));
    $tooEarly = newArticle(['published_at' => nicosia('2026-10-06 05:59')]);
    $read = newArticle(['published_at' => nicosia('2026-10-06 06:00'), 'view_count' => 500]);
    $featured = newArticle(['published_at' => nicosia('2026-10-06 22:00'), 'is_featured' => true, 'view_count' => 1]);
    $quiet = newArticle(['published_at' => nicosia('2026-10-07 05:59'), 'view_count' => 2]);
    newArticle(['published_at' => nicosia('2026-10-07 06:00')]);
    newArticle(['published_at' => nicosia('2026-10-06 12:00'), 'status' => 'draft']);

    $result = generator()->generate(DigestKind::News, DigestCadence::Daily, nicosia('2026-10-07 06:00'));

    expect($result->outcome)->toBe(GenerationOutcome::Created)
        ->and($result->digest->slug)->toBe('daily-news-2026-10-07')
        ->and($result->digest->status)->toBe(DigestStatus::Draft)
        ->and($result->digest->generated_automatically)->toBeTrue()
        ->and($result->digest->items->pluck('itemable_id')->all())->toBe([$featured->id, $read->id, $quiet->id])
        ->and($result->digest->items->pluck('itemable_id'))->not->toContain($tooEarly->id);
});

it('handles the daily window across the October clock change', function () {
    // 25 Oct 2026 03:00 EEST → 03:00 EET: the window is 25 hours long.
    $this->travelTo(nicosia('2026-10-25 06:00'));
    $justInside = newArticle(['published_at' => nicosia('2026-10-24 06:00')]);
    $afterChange = newArticle(['published_at' => nicosia('2026-10-25 05:30')]);

    $items = generator()->generate(DigestKind::News, DigestCadence::Daily, nicosia('2026-10-25 06:00'))->digest->items;

    expect($items->pluck('itemable_id')->sort()->values()->all())->toBe(collect([$justInside->id, $afterChange->id])->sort()->values()->all());
});

it('selects monthly news per section in menu order, most read first, capped', function () {
    section('news')->update(['sort_order' => 1]);
    section('startups')->update(['sort_order' => 2]);
    $startup = newArticle(['published_at' => nicosia('2026-09-10 10:00'), 'view_count' => 9999], section: 'startups');
    $newsTop = newArticle(['published_at' => nicosia('2026-09-01 00:30'), 'view_count' => 50]);
    $newsSecond = newArticle(['published_at' => nicosia('2026-09-30 23:30'), 'view_count' => 10]);
    newArticle(['published_at' => nicosia('2026-08-31 23:59'), 'view_count' => 99999]);
    newArticle(['published_at' => nicosia('2026-10-01 00:00'), 'view_count' => 99999]);
    foreach (range(1, 6) as $index) {
        newArticle(['published_at' => nicosia('2026-09-15 10:00'), 'view_count' => $index], section: 'startups');
    }

    $digest = generator()->generate(DigestKind::News, DigestCadence::Monthly, nicosia('2026-09-20'))->digest;
    $ids = $digest->items->pluck('itemable_id')->all();

    expect($digest->slug)->toBe('monthly-news-2026-09')
        ->and(array_slice($ids, 0, 3))->toBe([$newsTop->id, $newsSecond->id, $startup->id])
        ->and($ids)->toHaveCount(2 + DigestGenerator::MONTHLY_PER_SECTION);
});

it('selects weekly events Monday to Sunday, chronologically', function () {
    $sunday = newEvent(['starts_at' => nicosia('2026-10-18 23:00')]);
    $monday = newEvent(['starts_at' => nicosia('2026-10-12 00:00')]);
    newEvent(['starts_at' => nicosia('2026-10-11 23:59')]);
    newEvent(['starts_at' => nicosia('2026-10-19 00:00')]);

    $digest = generator()->generate(DigestKind::Events, DigestCadence::Weekly, nicosia('2026-10-14'))->digest;

    expect($digest->slug)->toBe('weekly-events-2026-W42')
        ->and($digest->period_start->toDateString())->toBe('2026-10-12')
        ->and($digest->period_end->toDateString())->toBe('2026-10-18')
        ->and($digest->items->pluck('itemable_id')->all())->toBe([$monday->id, $sunday->id]);
});

it('never creates duplicates for the same period', function () {
    $first = generator()->generate(DigestKind::Events, DigestCadence::Monthly, nicosia('2026-10-01 06:05'));
    $second = generator()->generate(DigestKind::Events, DigestCadence::Monthly, nicosia('2026-10-20'));

    expect($first->outcome)->toBe(GenerationOutcome::Created)
        ->and($second->outcome)->toBe(GenerationOutcome::SkippedExisting)
        ->and($second->digest->is($first->digest))->toBeTrue()
        ->and(Digest::query()->count())->toBe(1);
});

it('refreshes unedited drafts on request but never edited ones', function () {
    $digest = generator()->generate(DigestKind::Events, DigestCadence::Monthly, nicosia('2026-10-01'))->digest;
    $event = newEvent(['starts_at' => nicosia('2026-10-20 18:00')]);

    $refreshed = generator()->generate(DigestKind::Events, DigestCadence::Monthly, nicosia('2026-10-01'), GenerationMode::RefreshUnedited);
    expect($refreshed->outcome)->toBe(GenerationOutcome::Refreshed)
        ->and($refreshed->digest->items->pluck('itemable_id')->all())->toBe([$event->id]);

    $digest->refresh()->forceFill(['edited_at' => now()])->save();
    $digest->items()->delete();
    newEvent(['starts_at' => nicosia('2026-10-21 18:00')]);

    $skipped = generator()->generate(DigestKind::Events, DigestCadence::Monthly, nicosia('2026-10-01'), GenerationMode::RefreshUnedited);
    expect($skipped->outcome)->toBe(GenerationOutcome::SkippedEdited)
        ->and($digest->refresh()->items)->toHaveCount(0);
});

it('never touches published digests, even when overwriting', function () {
    $digest = generator()->generate(DigestKind::News, DigestCadence::Daily, nicosia('2026-10-07'))->digest;
    $digest->forceFill(['status' => DigestStatus::Published, 'published_at' => now()])->save();

    expect(generator()->generate(DigestKind::News, DigestCadence::Daily, nicosia('2026-10-07'), GenerationMode::Overwrite)->outcome)
        ->toBe(GenerationOutcome::SkippedPublished);
});

it('computes the scheduler reference period for each digest', function () {
    $sundayEvening = nicosia('2026-10-11 18:00');
    $firstOfMonth = nicosia('2026-11-01 06:00');

    expect(DigestGenerator::defaultReference(DigestKind::Events, DigestCadence::Weekly, $sundayEvening)->toDateString())->toBe('2026-10-12')
        ->and(DigestGenerator::defaultReference(DigestKind::News, DigestCadence::Monthly, $firstOfMonth)->format('Y-m'))->toBe('2026-10')
        ->and(DigestGenerator::defaultReference(DigestKind::Events, DigestCadence::Monthly, $firstOfMonth)->format('Y-m'))->toBe('2026-11')
        ->and(DigestGenerator::defaultReference(DigestKind::News, DigestCadence::Daily, nicosia('2026-10-07 06:00'))->toDateString())->toBe('2026-10-07');
});
