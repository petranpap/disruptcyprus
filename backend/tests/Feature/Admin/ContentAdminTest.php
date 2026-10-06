<?php

use App\Enums\ContentStatus;
use App\Enums\PushCampaignStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Industries\Pages\ListIndustries;
use App\Filament\Resources\PushCampaigns\Pages\CreatePushCampaign;
use App\Filament\Resources\PushCampaigns\Pages\ListPushCampaigns;
use App\Filament\Resources\Sections\Pages\EditSection;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Jobs\SendPushCampaign;
use App\Models\Event;
use App\Models\Industry;
use App\Models\PushCampaign;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel('admin'));

it('creates an online event in Cyprus time', function () {
    $this->actingAs(User::factory()->editor()->create());
    $industry = Industry::factory()->create();

    Livewire::test(CreateEvent::class)
        ->fillForm([
            'title' => ['en' => 'Founders Webinar', 'el' => 'Webinar Ιδρυτών'],
            'description' => ['en' => '<p>Online talk</p>', 'el' => '<p>Διαδικτυακή ομιλία</p>'],
            'starts_at' => '2026-11-10 18:00:00',
            'ends_at' => '2026-11-10 19:00:00',
            'is_online' => true,
            'online_url' => 'https://meet.example.com/founders',
            'industry_ids' => [$industry->id],
            'primary_industry_id' => $industry->id,
            'status' => 'published',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::query()->where('slug', 'founders-webinar')->firstOrFail();
    expect($event->starts_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-10 16:00')
        ->and($event->is_online)->toBeTrue()
        ->and($event->available_locales)->toBe(['el', 'en']);
});

it('requires the online link for online events and an end after the start', function () {
    $this->actingAs(User::factory()->editor()->create());
    $industry = Industry::factory()->create();

    Livewire::test(CreateEvent::class)
        ->fillForm([
            'title' => ['en' => 'Broken'],
            'starts_at' => '2026-11-10 18:00:00',
            'ends_at' => '2026-11-10 17:00:00',
            'is_online' => true,
            'industry_ids' => [$industry->id],
            'primary_industry_id' => $industry->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['online_url' => 'required', 'ends_at' => 'after']);
});

it('lets admins reorder industries', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$first, $second] = Industry::factory()->count(2)->sequence(['sort_order' => 1], ['sort_order' => 2])->create();

    Livewire::test(ListIndustries::class)
        ->call('reorderTable', [(string) $second->id, (string) $first->id]);

    expect($second->refresh()->sort_order)->toBeLessThan($first->refresh()->sort_order);
});

it('renames a section but never changes its slug', function () {
    $this->actingAs(User::factory()->admin()->create());
    $news = section('news');

    Livewire::test(EditSection::class, ['record' => $news->getRouteKey()])
        ->fillForm(['name' => ['el' => 'Νέα', 'en' => 'Latest'], 'slug' => 'hacked'])
        ->call('save')
        ->assertHasNoFormErrors();

    $news->refresh();
    expect($news->slug)->toBe('news')->and($news->getTranslation('name', 'el'))->toBe('Νέα');
});

it('lets admins change roles but not their own', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $reader = reader();

    Livewire::test(EditUser::class, ['record' => $reader->getRouteKey()])
        ->fillForm(['role' => 'editor'])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($reader->refresh()->role)->toBe(UserRole::Editor);

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->assertFormFieldDisabled('role');
});

it('exports users as CSV without secrets', function () {
    $this->actingAs(User::factory()->admin()->create());
    reader(['email' => 'csv@example.com']);

    $csv = ListUsers::exportCsv();
    ob_start();
    $csv->sendContent();
    $content = (string) ob_get_clean();

    expect($content)->toContain('id,name,email,role')->toContain('csv@example.com')->not->toContain('$2y$');
});

it('composes a campaign for followers of selected industries', function () {
    $editor = User::factory()->editor()->create();
    $this->actingAs($editor);
    $industry = Industry::factory()->create();

    Livewire::test(CreatePushCampaign::class)
        ->fillForm([
            'title' => ['el' => 'Νέο', 'en' => 'New'],
            'body' => ['el' => 'Μήνυμα', 'en' => 'Message'],
            'url' => '/events',
            'audience' => 'industries',
            'industry_ids' => [$industry->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $campaign = PushCampaign::query()->firstOrFail();
    expect($campaign->created_by)->toBe($editor->id)
        ->and($campaign->industry_ids)->toBe([$industry->id])
        ->and($campaign->status)->toBe(PushCampaignStatus::Draft);
});

it('sends a campaign through the queue, counts its audience and rate-limits senders', function () {
    Queue::fake();
    $editor = User::factory()->editor()->create();
    $this->actingAs($editor);
    RateLimiter::clear('push-campaigns:'.$editor->id);

    $industry = Industry::factory()->create();
    reader()->industries()->attach($industry);
    reader();

    $campaigns = collect(range(1, 4))->map(fn () => PushCampaign::query()->create([
        'title' => ['en' => 'Hi'], 'body' => ['en' => 'Body'], 'audience' => 'industries', 'industry_ids' => [$industry->id],
    ]));

    foreach ($campaigns as $campaign) {
        Livewire::test(ListPushCampaigns::class)->callTableAction('send', $campaign);
    }

    Queue::assertPushed(SendPushCampaign::class, 3);
    expect($campaigns->last()->refresh()->status)->toBe(PushCampaignStatus::Draft);

    (new SendPushCampaign($campaigns->first()->refresh()))->handle();
    expect($campaigns->first()->refresh())
        ->status->toBe(PushCampaignStatus::Sent)
        ->recipients_count->toBe(1);
});

it('archives articles in bulk without deleting them', function () {
    $this->actingAs(User::factory()->editor()->create());
    $articles = collect([newArticle(), newArticle()]);

    Livewire::test(ListArticles::class)
        ->callTableBulkAction('archive', $articles);

    expect($articles->map(fn ($article) => $article->refresh()->status)->unique()->all())->toBe([ContentStatus::Archived]);
});
