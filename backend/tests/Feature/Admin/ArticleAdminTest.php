<?php

use App\Enums\ContentStatus;
use App\Events\ContentPublished;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Article;
use App\Models\Author;
use App\Models\Industry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event as EventBus;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->editor = User::factory()->editor()->create(['locale' => 'en']);
    $this->actingAs($this->editor);
});

it('lists articles for editors', function () {
    $article = newArticle();

    Livewire::test(ListArticles::class)->assertOk()->assertCanSeeTableRecords([$article]);
});

it('creates an article with translations, industries and a primary industry', function () {
    EventBus::fake([ContentPublished::class]);
    [$fintech, $ai] = Industry::factory()->count(2)->create();
    $author = Author::factory()->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => ['el' => 'Νέα χρηματοδότηση', 'en' => 'New funding round'],
            'excerpt' => ['el' => 'Περίληψη', 'en' => 'Summary'],
            'body' => ['el' => '<p>Κείμενο άρθρου</p>', 'en' => '<p></p>'],
            'section_id' => section('startups')->id,
            'author_id' => $author->id,
            'industry_ids' => [$fintech->id, $ai->id],
            'primary_industry_id' => $ai->id,
            'status' => 'published',
            'is_featured' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::query()->where('slug', 'new-funding-round')->firstOrFail();

    expect($article->available_locales)->toBe(['el'])
        ->and($article->status)->toBe(ContentStatus::Published)
        ->and($article->published_at)->not->toBeNull()
        ->and($article->primaryIndustry()?->id)->toBe($ai->id)
        ->and($article->industries)->toHaveCount(2);

    EventBus::assertDispatched(ContentPublished::class, fn (ContentPublished $event) => $event->content->is($article));
});

it('schedules an article published in the future', function () {
    $industry = Industry::factory()->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => ['en' => 'Tomorrow'],
            'body' => ['en' => '<p>Soon</p>'],
            'section_id' => section()->id,
            'author_id' => Author::factory()->create()->id,
            'industry_ids' => [$industry->id],
            'primary_industry_id' => $industry->id,
            'status' => 'published',
            'published_at' => now()->addDay(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Article::query()->where('slug', 'tomorrow')->first()?->status)->toBe(ContentStatus::Scheduled);
});

it('requires a title in at least one language and a primary industry among the selected ones', function () {
    [$selected, $other] = Industry::factory()->count(2)->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => ['el' => '', 'en' => ''],
            'section_id' => section()->id,
            'author_id' => Author::factory()->create()->id,
            'industry_ids' => [$selected->id],
            'primary_industry_id' => $other->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['title.el' => 'required_without', 'primary_industry_id']);
});

it('edits translations and removes a language', function () {
    $industry = Industry::factory()->create();
    $article = newArticle([], [$industry]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertFormSet(['title.en' => $article->getTranslation('title', 'en'), 'primary_industry_id' => $industry->id])
        ->fillForm(['title' => ['el' => 'Ενημερωμένος', 'en' => ''], 'body' => ['en' => '']])
        ->call('save')
        ->assertHasNoFormErrors();

    $article->refresh();
    expect($article->getTranslation('title', 'el'))->toBe('Ενημερωμένος')
        ->and($article->available_locales)->toBe(['el']);
});

it('keeps slugs unique', function () {
    newArticle(['slug' => 'same-title']);
    $industry = Industry::factory()->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => ['en' => 'Same title'],
            'body' => ['en' => '<p>x</p>'],
            'section_id' => section()->id,
            'author_id' => Author::factory()->create()->id,
            'industry_ids' => [$industry->id],
            'primary_industry_id' => $industry->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Article::query()->where('slug', 'same-title-2')->exists())->toBeTrue();
});
