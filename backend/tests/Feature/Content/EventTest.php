<?php

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Models\Digest;
use App\Models\Event;
use App\Models\Industry;
use Carbon\CarbonImmutable;

beforeEach(function () {
    // Wednesday 7 October 2026, 12:00 in Nicosia.
    $this->travelTo(CarbonImmutable::parse('2026-10-07 12:00', 'Asia/Nicosia'));
});

function at(string $local): CarbonImmutable
{
    return CarbonImmutable::parse($local, 'Asia/Nicosia');
}

it('lists upcoming and ongoing events by start time', function () {
    $later = newEvent(['starts_at' => at('2026-10-20 18:00'), 'ends_at' => at('2026-10-20 21:00')]);
    $soon = newEvent(['starts_at' => at('2026-10-08 18:00'), 'ends_at' => at('2026-10-08 21:00')]);
    $ongoing = newEvent(['starts_at' => at('2026-10-07 09:00'), 'ends_at' => at('2026-10-07 17:00')]);
    newEvent(['starts_at' => at('2026-10-06 09:00'), 'ends_at' => at('2026-10-06 17:00')]);
    newEvent(['starts_at' => at('2026-10-09 09:00'), 'status' => 'draft']);

    $this->getJson('/api/v1/events')
        ->assertOk()
        ->assertJsonPath('meta.digest', null)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $ongoing->id)
        ->assertJsonPath('data.1.id', $soon->id)
        ->assertJsonPath('data.2.id', $later->id);
});

it('scopes the week and month tabs to Nicosia calendar periods and attaches the editorial digest', function () {
    $monday = newEvent(['starts_at' => at('2026-10-05 10:00'), 'ends_at' => at('2026-10-05 12:00')]);
    $sunday = newEvent(['starts_at' => at('2026-10-11 23:30'), 'ends_at' => at('2026-10-12 01:00')]);
    $nextWeek = newEvent(['starts_at' => at('2026-10-12 10:00'), 'ends_at' => at('2026-10-12 12:00')]);
    newEvent(['starts_at' => at('2026-11-01 00:30'), 'ends_at' => at('2026-11-01 02:00')]);

    Digest::factory()->create([
        'kind' => DigestKind::Events, 'cadence' => DigestCadence::Weekly,
        'period_start' => '2026-10-05', 'period_end' => '2026-10-11', 'slug' => 'weekly-events-2026-W41',
    ]);

    $week = $this->getJson('/api/v1/events?range=week')->assertOk()->assertJsonPath('meta.digest.slug', 'weekly-events-2026-W41');
    expect(collect($week->json('data'))->pluck('id')->all())->toBe([$monday->id, $sunday->id]);

    $month = $this->getJson('/api/v1/events?range=month')->assertOk()->assertJsonPath('meta.digest', null);
    expect(collect($month->json('data'))->pluck('id')->all())->toBe([$monday->id, $sunday->id, $nextWeek->id]);
});

it('filters by industry, city and online', function () {
    $fintech = Industry::factory()->create(['slug' => 'fintech']);
    $limassol = newEvent(['city' => 'Limassol', 'starts_at' => at('2026-10-09 10:00')], [$fintech]);
    $online = newEvent(['is_online' => true, 'city' => null, 'starts_at' => at('2026-10-10 10:00')], [$fintech]);
    newEvent(['city' => 'Nicosia', 'starts_at' => at('2026-10-09 10:00')]);

    expect(collect($this->getJson('/api/v1/events?industry=fintech')->json('data'))->pluck('id')->all())->toBe([$limassol->id, $online->id])
        ->and(collect($this->getJson('/api/v1/events?city=Limassol')->json('data'))->pluck('id')->all())->toBe([$limassol->id])
        ->and(collect($this->getJson('/api/v1/events?online=1')->json('data'))->pluck('id')->all())->toBe([$online->id]);

    $this->getJson('/api/v1/events?range=year')->assertUnprocessable()->assertJsonValidationErrors('range');
});

it('builds a month calendar with multi-day events on every day they cover', function () {
    $hackathon = newEvent(['starts_at' => at('2026-10-17 09:00'), 'ends_at' => at('2026-10-18 15:00')]);
    $meetup = newEvent(['starts_at' => at('2026-10-17 19:00'), 'ends_at' => at('2026-10-17 21:00')]);
    newEvent(['starts_at' => at('2026-11-02 10:00'), 'ends_at' => at('2026-11-02 12:00')]);

    $response = $this->getJson('/api/v1/events/calendar?month=2026-10')->assertOk()->assertJsonPath('data.month', '2026-10');

    $days = collect($response->json('data.days'))->mapWithKeys(fn ($day) => [$day['date'] => collect($day['events'])->pluck('id')->all()])->all();
    expect($days)->toBe(['2026-10-17' => [$hackathon->id, $meetup->id], '2026-10-18' => [$hackathon->id]]);

    $this->getJson('/api/v1/events/calendar?month=October')->assertUnprocessable();
});

it('shows an event and serves an iCalendar file', function () {
    $event = newEvent([
        'slug' => 'pitch-night',
        'title' => ['en' => 'Pitch Night; Seed, Edition', 'el' => 'Βραδιά Pitch'],
        'description' => ['en' => '<p>Eight startups pitch to investors.</p>', 'el' => '<p>Οκτώ startups παρουσιάζονται σε επενδυτές με μεγάλη περιγραφή που θα χρειαστεί αναδίπλωση γραμμής.</p>'],
        'starts_at' => at('2026-10-09 18:00'),
        'ends_at' => at('2026-10-09 21:00'),
    ]);

    $this->getJson('/api/v1/events/pitch-night')
        ->assertOk()
        ->assertJsonPath('data.slug', 'pitch-night')
        ->assertJsonPath('data.ics_url', route('events.ics', 'pitch-night'))
        ->assertJsonStructure(['data' => ['description', 'registration_url', 'online_url', 'address', 'share_url']]);

    $ics = $this->withHeader('Accept-Language', 'en')->get('/api/v1/events/pitch-night/ics')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="pitch-night.ics"')
        ->getContent();

    expect($ics)->toContain("DTSTART:20261009T150000Z\r\n")
        ->toContain('SUMMARY:Pitch Night\; Seed\, Edition')
        ->toContain("UID:event-{$event->id}@");

    $greek = $this->withHeader('Accept-Language', 'el')->get('/api/v1/events/pitch-night/ics')->getContent();
    foreach (explode("\r\n", $greek) as $line) {
        expect(strlen($line))->toBeLessThanOrEqual(75);
    }
    expect(str_replace("\r\n ", '', $greek))->toContain('αναδίπλωση γραμμής');
});

it('stores local times as the correct UTC instant', function () {
    $event = newEvent(['starts_at' => at('2026-10-11 23:30')]);

    expect(Event::query()->toBase()->where('id', $event->id)->value('starts_at'))->toBe('2026-10-11 20:30:00');
});

it('returns 404 for unknown or draft events', function () {
    newEvent(['slug' => 'draft-event', 'status' => 'draft']);

    $this->getJson('/api/v1/events/draft-event')->assertNotFound();
    $this->getJson('/api/v1/events/nope')->assertNotFound();
});
