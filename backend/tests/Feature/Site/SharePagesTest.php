<?php

use App\Models\Article;
use App\Models\Digest;
use App\Models\Industry;

it('renders an article with full text, social preview, hreflang and structured data', function () {
    $industry = Industry::factory()->create(['name' => ['en' => 'FinTech', 'el' => 'FinTech']]);
    $article = newArticle([
        'title' => ['en' => 'Seed round closes', 'el' => 'Έκλεισε γύρος seed'],
        'excerpt' => ['en' => 'A Limassol startup raised €2M.', 'el' => 'Startup από τη Λεμεσό άντλησε €2 εκατ.'],
        'body' => ['en' => '<p>Full story here.</p><script>alert(1)</script>', 'el' => '<p>Ολόκληρο το θέμα.</p>'],
    ], [$industry]);

    $response = $this->get('/a/'.$article->slug)->assertOk();

    $response->assertSee('<html lang="el">', false)
        ->assertSee('Έκλεισε γύρος seed')
        ->assertSee('<p>Ολόκληρο το θέμα.</p>', false)
        ->assertSee('<meta property="og:title" content="Έκλεισε γύρος seed">', false)
        ->assertSee('<link rel="canonical" href="'.url('/a/'.$article->slug).'">', false)
        ->assertSee('hreflang="en" href="'.url('/a/'.$article->slug).'?lang=en"', false)
        ->assertSee('"@type":"NewsArticle"', false)
        ->assertSee(config('app.frontend_url').'/articles/'.$article->slug, false)
        ->assertHeaderMissing('Set-Cookie');

    $this->get('/a/'.$article->slug.'?lang=en')
        ->assertOk()
        ->assertSee('Seed round closes')
        ->assertSee('<p>Full story here.</p>', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('falls back to the language the article was written in', function () {
    $article = Article::factory()->onlyIn('en')->for(section())->create(['title' => ['en' => 'English only']]);

    $this->get('/a/'.$article->slug)
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertSee('English only')
        ->assertDontSee('hreflang="el"', false);
});

it('hides unpublished content', function () {
    $draft = newArticle(['status' => 'draft', 'published_at' => null]);
    $event = newEvent(['status' => 'draft', 'published_at' => null]);

    $this->get('/a/'.$draft->slug)->assertNotFound();
    $this->get('/e/'.$event->slug)->assertNotFound();
    $this->get('/a/does-not-exist')->assertNotFound();
});

it('renders an event with its schedule, a tidy venue and Event structured data', function () {
    $event = newEvent([
        'title' => ['en' => 'Pitch Night', 'el' => 'Βραδιά Pitch'],
        'starts_at' => now()->addDays(3)->setTime(15, 0),
        'ends_at' => now()->addDays(3)->setTime(18, 0),
        'location_name' => 'Port Tech Quarter',
        'address' => 'Port Tech Quarter, Larnaca',
        'city' => 'Larnaca',
        'is_online' => false,
        'registration_url' => 'https://example.com/register',
    ]);

    $this->get('/e/'.$event->slug.'?lang=en')
        ->assertOk()
        ->assertSee('Pitch Night')
        ->assertSee('Port Tech Quarter, Larnaca')
        ->assertDontSee('Port Tech Quarter, Port Tech Quarter')
        ->assertSee('Cyprus time')
        ->assertSee('"@type":"Event"', false)
        ->assertSee('"eventAttendanceMode":"https://schema.org/OfflineEventAttendanceMode"', false)
        ->assertSee('https://example.com/register', false)
        ->assertSee('/api/v1/events/'.$event->slug.'/ics', false);
});

it('renders a digest with its published items in editor order', function () {
    $first = newArticle(['title' => ['en' => 'First story', 'el' => 'Πρώτο θέμα']]);
    $second = newEvent(['title' => ['en' => 'An event', 'el' => 'Μια εκδήλωση']]);
    $hidden = newArticle(['title' => ['en' => 'Hidden', 'el' => 'Κρυφό'], 'status' => 'draft', 'published_at' => null]);
    $digest = Digest::factory()->create();
    foreach ([$first, $second, $hidden] as $position => $item) {
        $digest->items()->create(['itemable_type' => $item->getMorphClass(), 'itemable_id' => $item->id, 'position' => $position + 1]);
    }

    $this->get('/d/'.$digest->slug)
        ->assertOk()
        ->assertSeeInOrder(['Πρώτο θέμα', 'Μια εκδήλωση'])
        ->assertDontSee('Κρυφό')
        ->assertSee('/a/'.$first->slug, false)
        ->assertSee('/e/'.$second->slug, false);

    Digest::factory()->create(['slug' => 'draft-digest', 'status' => 'draft', 'published_at' => null]);
    $this->get('/d/draft-digest')->assertNotFound();
});
