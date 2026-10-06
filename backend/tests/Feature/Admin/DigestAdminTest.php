<?php

use App\Enums\DigestStatus;
use App\Events\DigestPublished;
use App\Filament\Resources\Digests\Pages\CreateDigest;
use App\Filament\Resources\Digests\Pages\EditDigest;
use App\Models\Digest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event as EventBus;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->editor()->create(['locale' => 'en']));
    $this->travelTo(CarbonImmutable::parse('2026-10-07 10:00', 'Asia/Nicosia'));
});

it('creates a digest through the generator, pre-filled for the period', function () {
    $article = newArticle(['published_at' => CarbonImmutable::parse('2026-10-06 20:00', 'Asia/Nicosia'), 'is_featured' => true]);
    newArticle(['published_at' => CarbonImmutable::parse('2026-10-05 20:00', 'Asia/Nicosia')]);

    Livewire::test(CreateDigest::class)
        ->fillForm(['kind' => 'news', 'cadence' => 'daily', 'reference_date' => '2026-10-07'])
        ->call('create')
        ->assertHasNoFormErrors();

    $digest = Digest::query()->where('slug', 'daily-news-2026-10-07')->firstOrFail();
    expect($digest->items->pluck('itemable_id')->all())->toBe([$article->id])
        ->and($digest->status)->toBe(DigestStatus::Draft);
});

it('refuses to duplicate a period', function () {
    Livewire::test(CreateDigest::class)->fillForm(['kind' => 'news', 'cadence' => 'daily', 'reference_date' => '2026-10-07'])->call('create');
    Livewire::test(CreateDigest::class)->fillForm(['kind' => 'news', 'cadence' => 'daily', 'reference_date' => '2026-10-07'])->call('create');

    expect(Digest::query()->count())->toBe(1);
});

it('marks a draft as edited when an editor reorders items and adds notes', function () {
    $first = newArticle(['published_at' => CarbonImmutable::parse('2026-10-06 20:00', 'Asia/Nicosia'), 'is_featured' => true]);
    $second = newArticle(['published_at' => CarbonImmutable::parse('2026-10-06 21:00', 'Asia/Nicosia')]);
    Livewire::test(CreateDigest::class)->fillForm(['kind' => 'news', 'cadence' => 'daily', 'reference_date' => '2026-10-07'])->call('create');
    $digest = Digest::query()->firstOrFail();
    [$itemA, $itemB] = $digest->items->values();

    Livewire::test(EditDigest::class, ['record' => $digest->getRouteKey()])
        ->fillForm(['intro' => ['en' => 'Good morning', 'el' => 'Καλημέρα']])
        ->set('data.items', [
            "record-{$itemB->id}" => ['itemable_id' => $second->id, 'editor_note' => ['en' => 'Must read', 'el' => '']],
            "record-{$itemA->id}" => ['itemable_id' => $first->id, 'editor_note' => ['en' => '', 'el' => '']],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $digest->refresh();
    expect($digest->edited_at)->not->toBeNull()
        ->and($digest->items->pluck('itemable_id')->all())->toBe([$second->id, $first->id])
        ->and($digest->items->first()->getTranslation('editor_note', 'en'))->toBe('Must read')
        ->and($digest->getTranslation('intro', 'el'))->toBe('Καλημέρα');
});

it('regenerates a draft on request even if edited', function () {
    Livewire::test(CreateDigest::class)->fillForm(['kind' => 'news', 'cadence' => 'daily', 'reference_date' => '2026-10-07'])->call('create');
    $digest = Digest::query()->firstOrFail();
    $digest->forceFill(['edited_at' => now()])->save();
    $late = newArticle(['published_at' => CarbonImmutable::parse('2026-10-07 05:00', 'Asia/Nicosia')]);

    Livewire::test(EditDigest::class, ['record' => $digest->getRouteKey()])->callAction('regenerate');

    $digest->refresh();
    expect($digest->edited_at)->toBeNull()->and($digest->items->pluck('itemable_id')->all())->toBe([$late->id]);
});

it('publishes and notifies', function () {
    EventBus::fake([DigestPublished::class]);
    Livewire::test(CreateDigest::class)->fillForm(['kind' => 'events', 'cadence' => 'weekly', 'reference_date' => '2026-10-07'])->call('create');
    $digest = Digest::query()->firstOrFail();

    Livewire::test(EditDigest::class, ['record' => $digest->getRouteKey()])->callAction('publish');

    expect($digest->refresh()->status)->toBe(DigestStatus::Published)->and($digest->published_at)->not->toBeNull();
    EventBus::assertDispatched(DigestPublished::class, fn (DigestPublished $event) => $event->digest->is($digest));
});
