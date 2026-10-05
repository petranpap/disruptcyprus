<?php

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;

it('covers the 24h before 06:00 Nicosia time for daily news, across the DST change', function () {
    // 25 October 2026: Cyprus switches from UTC+3 to UTC+2.
    $period = DigestPeriod::for(DigestKind::News, DigestCadence::Daily, CarbonImmutable::parse('2026-10-25 07:00', 'Asia/Nicosia'));
    [$from, $to] = $period->window();

    expect($period->slug())->toBe('daily-news-2026-10-25')
        ->and($from->toIso8601String())->toBe('2026-10-24T03:00:00+00:00')
        ->and($to->toIso8601String())->toBe('2026-10-25T04:00:00+00:00');
});

it('uses the Nicosia calendar day, not UTC', function () {
    // 23:30 UTC on 4 October is already 5 October in Nicosia.
    $period = DigestPeriod::for(DigestKind::News, DigestCadence::Daily, CarbonImmutable::parse('2026-10-04 23:30', 'UTC'));

    expect($period->start->toDateString())->toBe('2026-10-05');
});

it('spans Monday to Sunday for weekly events', function () {
    $period = DigestPeriod::for(DigestKind::Events, DigestCadence::Weekly, CarbonImmutable::parse('2026-10-08 12:00', 'Asia/Nicosia'));

    expect($period->start->toDateString())->toBe('2026-10-05')
        ->and($period->end->toDateString())->toBe('2026-10-11')
        ->and($period->slug())->toBe('weekly-events-2026-W41')
        ->and($period->titles())->toBe([
            'en' => 'Events this week — 5–11 October',
            'el' => 'Εκδηλώσεις της εβδομάδας — 5–11 Οκτωβρίου',
        ]);
});

it('spans the calendar month for monthly digests', function () {
    $period = DigestPeriod::for(DigestKind::News, DigestCadence::Monthly, CarbonImmutable::parse('2026-09-15', 'Asia/Nicosia'));
    [$from, $to] = $period->window();

    expect($period->slug())->toBe('monthly-news-2026-09')
        ->and($from->toIso8601String())->toBe('2026-08-31T21:00:00+00:00')
        ->and($to->toIso8601String())->toBe('2026-09-30T21:00:00+00:00')
        ->and($period->titles()['el'])->toBe('Μηνιαία Ανασκόπηση — Σεπτέμβριος 2026');
});

it('rejects unsupported combinations', function () {
    DigestPeriod::for(DigestKind::News, DigestCadence::Weekly, CarbonImmutable::now());
})->throws(InvalidArgumentException::class);
