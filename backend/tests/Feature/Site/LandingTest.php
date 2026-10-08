<?php

it('serves the landing page in Greek at / and in English at /en', function () {
    $article = newArticle(['title' => ['en' => 'Latest English story', 'el' => 'Τελευταίο θέμα']]);
    newEvent(['title' => ['en' => 'Upcoming meetup', 'el' => 'Επερχόμενο meetup'], 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2)]);

    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="el">', false)
        ->assertSee('Τελευταίο θέμα')
        ->assertSee('Επερχόμενο meetup')
        ->assertSee('/a/'.$article->slug, false)
        ->assertSee('hreflang="en" href="'.url('/en').'"', false)
        ->assertHeader('Cache-Control', 'max-age=300, public')
        ->assertHeaderMissing('Set-Cookie');

    $this->get('/en')
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertSee('Latest English story')
        ->assertSee('/a/'.$article->slug.'?lang=en', false);
});

it('serves the about, contact and legal pages in both languages', function (string $page) {
    $this->get('/'.$page)->assertOk()->assertSee('<html lang="el">', false);
    $this->get('/'.$page.'?lang=en')->assertOk()->assertSee('<html lang="en">', false);
})->with(['about', 'contact', 'privacy', 'terms']);

it('lists published content with language alternates in the sitemap', function () {
    $article = newArticle();
    $draft = newArticle(['status' => 'draft', 'published_at' => null]);
    $event = newEvent();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.url('/a/'.$article->slug).'</loc>', false)
        ->assertSee('<loc>'.url('/a/'.$article->slug).'?lang=en</loc>', false)
        ->assertSee('<loc>'.url('/e/'.$event->slug).'</loc>', false)
        ->assertSee('<loc>'.url('/privacy').'</loc>', false)
        ->assertDontSee('/a/'.$draft->slug, false);
});

it('points crawlers at the sitemap and away from the admin', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.url('/sitemap.xml'));
});
