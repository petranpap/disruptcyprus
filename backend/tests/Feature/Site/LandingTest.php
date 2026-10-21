<?php

use App\Http\Middleware\SecurityHeaders;

it('serves the landing page in English at / and in Greek at /el', function () {
    $article = newArticle(['title' => ['en' => 'Latest English story', 'el' => 'Τελευταίο θέμα']]);
    newEvent(['title' => ['en' => 'Upcoming meetup', 'el' => 'Επερχόμενο meetup'], 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2)]);

    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertSee('Latest English story')
        ->assertSee('Upcoming meetup')
        ->assertSee('/a/'.$article->slug, false)
        ->assertSee('hreflang="el" href="'.url('/el').'"', false)
        ->assertSee('hreflang="x-default" href="'.url('/').'"', false)
        ->assertHeader('Cache-Control', 'max-age=300, public')
        ->assertHeaderMissing('Set-Cookie');

    $this->get('/el')
        ->assertOk()
        ->assertSee('<html lang="el">', false)
        ->assertSee('Τελευταίο θέμα')
        ->assertSee('/a/'.$article->slug.'?lang=el', false);
});

it('serves the about, contact and legal pages in both languages', function (string $page) {
    $this->get('/'.$page)->assertOk()->assertSee('<html lang="en">', false);
    $this->get('/'.$page.'?lang=el')->assertOk()->assertSee('<html lang="el">', false);
})->with(['about', 'contact', 'privacy', 'terms']);

it('lists published content with language alternates in the sitemap', function () {
    $article = newArticle();
    $draft = newArticle(['status' => 'draft', 'published_at' => null]);
    $event = newEvent();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.url('/a/'.$article->slug).'</loc>', false)
        ->assertSee('<loc>'.url('/a/'.$article->slug).'?lang=el</loc>', false)
        ->assertSee('<loc>'.url('/e/'.$event->slug).'</loc>', false)
        ->assertSee('<loc>'.url('/privacy').'</loc>', false)
        ->assertDontSee('/a/'.$draft->slug, false);
});

it('points crawlers at the sitemap and away from the admin', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertDontSee('backend')
        ->assertSee('Sitemap: '.url('/sitemap.xml'));
});

it('sends security headers, with a strict CSP on public pages', function () {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Content-Security-Policy', SecurityHeaders::SITE_CSP);

    $this->getJson('/api/v1/sections')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeaderMissing('Content-Security-Policy');
});

it('uses root-relative media URLs so the app host serves images same-origin', function () {
    expect(Storage::disk('public')->url('1/photo.webp'))->toBe('/storage/1/photo.webp');
});

it('keeps the admin panel at /backend, unlisted and unindexed', function () {
    $this->get('/admin')->assertNotFound();
    $this->get('/backend/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    // Public pages and robots.txt never name the admin path.
    $this->get('/robots.txt')->assertDontSee('backend');
    $this->get('/')->assertDontSee('/backend', false);
    config(['preview.enabled' => true]);
    $this->get('/')->assertDontSee('/backend', false);
});
