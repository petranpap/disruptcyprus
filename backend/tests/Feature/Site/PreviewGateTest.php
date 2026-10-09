<?php

use App\Filament\Resources\WaitlistSignups\Pages\ListWaitlistSignups;
use App\Http\Middleware\IssuePreviewAccess;
use App\Models\User;
use App\Models\WaitlistSignup;
use App\Support\Preview\PreviewAccess;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Livewire\Livewire;

beforeEach(function () {
    config(['preview.enabled' => true]);
});

function accessCookieFor(User $user): string
{
    return PreviewAccess::cookieFor($user)->getValue();
}

describe('coming-soon page', function () {
    it('replaces the site in Greek or English, without caching or indexing', function () {
        $article = newArticle();

        $this->get('/')
            ->assertOk()
            ->assertSee('Το Disrupt Cyprus έρχεται σύντομα')
            ->assertSee('name="email"', false)
            ->assertDontSee('name="password"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeaderMissing('Set-Cookie');

        $this->get('/?lang=en')->assertOk()->assertSee('Disrupt Cyprus is launching soon');
        $this->get('/a/'.$article->slug)->assertOk()->assertSee('name="email"', false);
    });

    it('locks the API with a clear error code', function () {
        $this->getJson('/api/v1/sections')->assertForbidden()->assertJsonPath('code', 'preview_locked');
    });

    it('keeps crawlers out', function () {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /')->assertDontSee('Sitemap:');
    });

    it('changes nothing when the gate is off', function () {
        config(['preview.enabled' => false]);

        $this->get('/')->assertOk()->assertDontSee('name="email"', false);
        $this->getJson('/api/v1/sections')->assertOk();
    });
});

describe('waitlist', function () {
    it('adds a signup with consent and shows the thank-you state', function () {
        $this->post('/waitlist?lang=en', ['name' => ' Anna K ', 'email' => 'Anna@Example.com ', 'consent' => '1'])
            ->assertRedirect(url('/').'?lang=en&joined=1#waitlist');

        $signup = WaitlistSignup::query()->sole();
        expect($signup->name)->toBe('Anna K')
            ->and($signup->email)->toBe('anna@example.com')
            ->and($signup->locale)->toBe('en')
            ->and($signup->consented_at)->not->toBeNull();

        $this->get('/?lang=en&joined=1')->assertOk()->assertSee('You are on the list')->assertDontSee('name="email"', false);
    });

    it('never reveals whether an email is already on the list', function () {
        WaitlistSignup::query()->create(['name' => 'First', 'email' => 'anna@example.com', 'locale' => 'el', 'consented_at' => now()]);

        $this->post('/waitlist', ['name' => 'Second', 'email' => 'anna@example.com', 'consent' => '1'])
            ->assertRedirect(url('/').'?joined=1#waitlist');

        expect(WaitlistSignup::query()->sole()->name)->toBe('First');
    });

    it('explains invalid input in the page language and keeps what was typed', function () {
        $this->post('/waitlist?lang=en', ['name' => 'Anna', 'email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertSee('Please check the highlighted fields.')
            ->assertSee('value="Anna"', false)
            ->assertSee('id="email-error"', false)
            ->assertSee('id="consent-error"', false);

        $this->post('/waitlist', ['name' => '', 'email' => '', 'consent' => '1'])
            ->assertStatus(422)
            ->assertSee('Έλεγξε τα πεδία');

        expect(WaitlistSignup::query()->count())->toBe(0);
    });

    it('quietly ignores bots that fill the hidden field', function () {
        $this->post('/waitlist', ['name' => 'Bot', 'email' => 'bot@example.com', 'consent' => '1', 'website' => 'http://spam.example'])
            ->assertRedirect(url('/').'?joined=1#waitlist');

        expect(WaitlistSignup::query()->count())->toBe(0);
    });

    it('is shown to admins only, with a CSV export', function () {
        WaitlistSignup::query()->create(['name' => '=HYPERLINK("x")', 'email' => 'a@example.com', 'locale' => 'el', 'consented_at' => now()]);
        Filament::setCurrentPanel('admin');

        $this->actingAs(User::factory()->editor()->create())->get('/admin/waitlist')->assertForbidden();

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ListWaitlistSignups::class)
            ->assertCanSeeTableRecords(WaitlistSignup::all())
            ->callAction('export')
            ->assertFileDownloaded('disrupt-cyprus-waitlist-'.now()->format('Y-m-d').'.csv');
    });
});

describe('staff access', function () {
    it('opens the site and the API for staff who signed in to the admin, without any shared password', function () {
        $editor = User::factory()->editor()->create();

        $this->withUnencryptedCookie('dc_preview', accessCookieFor($editor))
            ->get('/')
            ->assertOk()
            ->assertDontSee('name="email"', false);

        $this->withCredentials()->withUnencryptedCookie('dc_preview', accessCookieFor($editor))
            ->getJson('/api/v1/sections')->assertOk();
    });

    it('issues the access cookie when staff open the admin panel', function () {
        $editor = User::factory()->editor()->create();
        Filament::setCurrentPanel('admin');

        $response = $this->actingAs($editor)->get('/admin');

        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($cookie) => $cookie->getName() === 'dc_preview');
        expect($cookie)->not->toBeNull()
            ->and($cookie->isHttpOnly())->toBeTrue()
            ->and(PreviewAccess::granted(Request::create('/', cookies: ['dc_preview' => $cookie->getValue()])))->toBeTrue();
    });

    it('never gives readers access', function () {
        $reader = reader();
        $request = Request::create('/admin');
        $request->setUserResolver(fn () => $reader);

        app(IssuePreviewAccess::class)->handle($request, fn () => response('ok'));

        expect(cookie()->getQueuedCookies())->toBeEmpty()
            ->and(PreviewAccess::granted(Request::create('/', cookies: ['dc_preview' => accessCookieFor($reader)])))->toBeFalse();
    });

    it('rejects forged, tampered and expired cookies', function () {
        $editor = User::factory()->editor()->create();
        $valid = accessCookieFor($editor);
        [$id, $expires, $signature] = explode('.', $valid);
        $check = fn (string $value) => PreviewAccess::granted(Request::create('/', cookies: ['dc_preview' => $value]));

        expect($check($valid))->toBeTrue()
            ->and($check('forged'))->toBeFalse()
            ->and($check(($id + 1).".{$expires}.{$signature}"))->toBeFalse()
            ->and($check("{$id}.".($expires + 999).".{$signature}"))->toBeFalse();

        $this->travel(15)->days();
        expect($check($valid))->toBeFalse();
    });

    it('shows staff a preview bar on the real site, and nothing when the gate is off', function () {
        $editor = User::factory()->editor()->create();

        $this->withUnencryptedCookie('dc_preview', accessCookieFor($editor))
            ->get('/en')
            ->assertSee('Everyone else sees the coming-soon page.');

        config(['preview.enabled' => false]);
        $this->get('/en')->assertDontSee('Everyone else sees the coming-soon page.');
    });

    it('stops working as soon as the account is no longer staff', function () {
        $editor = User::factory()->editor()->create();
        $cookie = accessCookieFor($editor);

        $editor->forceFill(['role' => 'reader'])->save();

        expect(PreviewAccess::granted(Request::create('/', cookies: ['dc_preview' => $cookie])))->toBeFalse();
    });
});
