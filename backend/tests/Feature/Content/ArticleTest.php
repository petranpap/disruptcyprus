<?php

use App\Models\Article;
use App\Models\Industry;
use Illuminate\Support\Facades\DB;

it('shows a published article with sanitized body and share link', function () {
    $article = newArticle(['slug' => 'safe-article']);
    $article->setTranslation('body', 'en', '<p>Hello <script>alert(1)</script><a href="https://example.com" onclick="x()">link</a></p><img src="javascript:alert(1)">')->save();

    $response = $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/articles/safe-article')->assertOk();

    expect($response->json('data.body'))
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->toContain('rel="noopener noreferrer nofollow"')
        ->and($response->json('data.share_url'))->toEndWith('/a/safe-article')
        ->and($response->json('data.available_locales'))->toBe(['el', 'en'])
        ->and($response->json('data.author'))->toHaveKeys(['name', 'title', 'bio', 'avatar_url', 'is_verified']);
});

it('hides drafts and scheduled articles', function () {
    newArticle(['slug' => 'draft', 'status' => 'draft', 'published_at' => null]);
    newArticle(['slug' => 'later', 'status' => 'scheduled', 'published_at' => now()->addHour()]);

    $this->getJson('/api/v1/articles/draft')->assertNotFound();
    $this->getJson('/api/v1/articles/later')->assertNotFound();
});

it('falls back to the other language when opened directly', function () {
    newArticle(['slug' => 'greek'])->replaceTranslations('title', ['el' => 'Τίτλος'])->replaceTranslations('body', ['el' => '<p>Σώμα</p>'])->save();

    $this->actingAs(reader(['content_locales' => ['en']]))->withHeader('Accept-Language', 'en')
        ->getJson('/api/v1/articles/greek')
        ->assertOk()
        ->assertJsonPath('data.locale', 'el')
        ->assertJsonPath('data.is_fallback', true)
        ->assertJsonPath('data.title', 'Τίτλος');
});

it('returns related articles ordered by shared industries', function () {
    [$fintech, $ai, $maritime] = Industry::factory()->count(3)->create();
    $article = newArticle(['slug' => 'main'], [$fintech, $ai]);
    $sharesTwo = newArticle(['published_at' => now()->subDays(3)], [$ai, $fintech]);
    $sharesOne = newArticle(['published_at' => now()->subHour()], [$fintech]);
    newArticle([], [$maritime]);

    $this->getJson('/api/v1/articles/main/related')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $sharesTwo->id)
        ->assertJsonPath('data.1.id', $sharesOne->id);
});

it('records a view once per viewer within 30 minutes', function () {
    $article = newArticle(['view_count' => 0]);

    $this->postJson("/api/v1/articles/{$article->id}/view")->assertNoContent();
    $this->postJson("/api/v1/articles/{$article->id}/view")->assertNoContent();

    expect(Article::query()->find($article->id)->view_count)->toBe(1)
        ->and(DB::table('content_view_stats')->where('viewable_type', 'article')->where('viewable_id', $article->id)->sum('views'))->toEqual(1);

    $this->travel(31)->minutes();
    $this->postJson("/api/v1/articles/{$article->id}/view")->assertNoContent();

    expect(Article::query()->find($article->id)->view_count)->toBe(2);
});

it('does not record views for unpublished articles', function () {
    $draft = newArticle(['status' => 'draft', 'published_at' => null]);

    $this->postJson("/api/v1/articles/{$draft->id}/view")->assertNotFound();
});
