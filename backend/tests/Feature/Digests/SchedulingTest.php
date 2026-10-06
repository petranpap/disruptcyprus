<?php

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Events\ContentPublished;
use App\Models\Article;
use App\Models\Digest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as EventBus;
use Illuminate\Support\Facades\Storage;

it('generates digests from the command, idempotently', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-11 18:00', 'Asia/Nicosia'));

    $this->artisan('digests:generate events weekly')->expectsOutputToContain('created: weekly-events-2026-W42')->assertSuccessful();
    $this->artisan('digests:generate events weekly')->expectsOutputToContain('skipped_existing')->assertSuccessful();
    $this->artisan('digests:generate news daily --date=2026-10-07')->expectsOutputToContain('daily-news-2026-10-07')->assertSuccessful();
    $this->artisan('digests:generate news weekly')->assertFailed();
    $this->artisan('digests:generate podcasts daily')->assertFailed();

    expect(Digest::query()->count())->toBe(2);
});

it('registers the digest schedule in Cyprus time', function () {
    $events = collect(app(Schedule::class)->events())
        ->mapWithKeys(fn ($event) => [trim(str_replace(["'".PHP_BINARY."'", PHP_BINARY, "'artisan'", 'artisan'], '', $event->command)) => [$event->expression, $event->timezone]]);

    expect($events->get('digests:generate news daily'))->toBe(['0 6 * * *', 'Asia/Nicosia'])
        ->and($events->get('digests:generate events weekly'))->toBe(['0 18 * * 0', 'Asia/Nicosia'])
        ->and($events->get('digests:generate news monthly'))->toBe(['0 6 1 * *', 'Asia/Nicosia'])
        ->and($events->get('digests:generate events monthly'))->toBe(['5 6 1 * *', 'Asia/Nicosia'])
        ->and($events->get('content:publish-scheduled')[0])->toBe('* * * * *');
});

it('normalizes publication state on save', function () {
    $now = newArticle(['status' => ContentStatus::Published, 'published_at' => null]);
    $future = newArticle(['status' => ContentStatus::Published, 'published_at' => now()->addHour()]);
    $undated = newArticle(['status' => ContentStatus::Scheduled, 'published_at' => null]);

    expect($now->published_at)->not->toBeNull()
        ->and($future->status)->toBe(ContentStatus::Scheduled)
        ->and($undated->status)->toBe(ContentStatus::Draft);
});

it('publishes scheduled content when due and announces it once', function () {
    EventBus::fake([ContentPublished::class]);
    $due = newArticle(['status' => ContentStatus::Scheduled, 'published_at' => now()->addMinutes(5)]);
    $later = newArticle(['status' => ContentStatus::Scheduled, 'published_at' => now()->addDay()]);
    EventBus::assertNotDispatched(ContentPublished::class);

    $this->travel(10)->minutes();
    $this->artisan('content:publish-scheduled')->expectsOutputToContain('Published 1 item(s).')->assertSuccessful();
    $this->artisan('content:publish-scheduled')->expectsOutputToContain('Published 0 item(s).');

    expect(Article::query()->find($due->id)->status)->toBe(ContentStatus::Published)
        ->and(Article::query()->find($later->id)->status)->toBe(ContentStatus::Scheduled);
    EventBus::assertDispatchedTimes(ContentPublished::class, 1);
});

it('prunes old view buckets and expired exports', function () {
    Storage::fake('local');
    DB::table('content_view_stats')->insert([
        ['viewable_type' => 'article', 'viewable_id' => 1, 'bucket_at' => now()->subDays(40), 'views' => 1],
        ['viewable_type' => 'article', 'viewable_id' => 1, 'bucket_at' => now()->subDay(), 'views' => 1],
    ]);
    Storage::disk('local')->put('exports/1/old.json', '{}');
    Storage::disk('local')->put('exports/1/new.json', '{}');
    touch(Storage::disk('local')->path('exports/1/old.json'), now()->subDays(3)->getTimestamp());

    $this->artisan('maintenance:prune')->assertSuccessful();

    expect(DB::table('content_view_stats')->count())->toBe(1)
        ->and(Storage::disk('local')->exists('exports/1/old.json'))->toBeFalse()
        ->and(Storage::disk('local')->exists('exports/1/new.json'))->toBeTrue();
});

it('creates or promotes staff accounts from the command line', function () {
    $this->artisan('admin:user', ['email' => 'Chief@Example.com', '--name' => 'Chief'])
        ->expectsQuestion('Password (leave empty to generate one)', 'secret-pass-123')
        ->assertSuccessful();

    $chief = User::query()->where('email', 'chief@example.com')->firstOrFail();
    expect($chief->role)->toBe(UserRole::Admin)->and($chief->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue();

    $reader = reader();
    $this->artisan('admin:user', ['email' => $reader->email, '--role' => 'editor'])->assertSuccessful();
    expect($reader->refresh()->role)->toBe(UserRole::Editor);

    $this->artisan('admin:user', ['email' => 'x@example.com', '--role' => 'reader'])->assertFailed();
});
