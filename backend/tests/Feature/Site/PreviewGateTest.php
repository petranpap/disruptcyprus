<?php

use App\Support\Preview\PreviewAccess;

beforeEach(function () {
    config(['preview.enabled' => true, 'preview.username' => 'team', 'preview.password' => 'launch-2026']);
});

function previewCookie(): string
{
    return PreviewAccess::cookie()->getValue();
}

it('shows the coming-soon page instead of the site, in Greek or English, without caching or indexing', function () {
    $article = newArticle();

    $this->get('/')
        ->assertOk()
        ->assertSee('Το Disrupt Cyprus έρχεται σύντομα')
        ->assertSee('name="password"', false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeaderMissing('Set-Cookie');

    $this->get('/?lang=en')->assertOk()->assertSee('Disrupt Cyprus is launching soon');
    $this->get('/a/'.$article->slug)->assertOk()->assertSee('name="next" value="/a/'.$article->slug.'"', false);
});

it('locks the API with a clear error code', function () {
    $this->getJson('/api/v1/sections')->assertForbidden()->assertJsonPath('code', 'preview_locked');
});

it('signs the team in with the shared credentials and sends them to the app', function () {
    $response = $this->post('/preview/login', ['username' => 'team', 'password' => 'launch-2026', 'next' => 'app'])
        ->assertRedirect(config('app.frontend_url').'/');

    $cookie = collect($response->headers->getCookies())->firstWhere(fn ($cookie) => $cookie->getName() === 'dc_preview');
    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue();
});

it('returns to the shared page the visitor asked for, and never to another site', function (string $next, string $expected) {
    $this->post('/preview/login', ['username' => 'team', 'password' => 'launch-2026', 'next' => $next])
        ->assertRedirect($expected === 'app' ? config('app.frontend_url').'/' : url($expected));
})->with([
    'share page' => ['/a/seed-round', '/a/seed-round'],
    'other site' => ['//evil.example', 'app'],
    'absolute url' => ['https://evil.example', 'app'],
    'backslash trick' => ['/\\evil.example', 'app'],
]);

it('rejects wrong credentials', function () {
    $this->post('/preview/login', ['username' => 'team', 'password' => 'wrong'])
        ->assertStatus(422)
        ->assertSee('Τα στοιχεία δεν είναι σωστά');

    $this->post('/preview/login', ['username' => '', 'password' => ''])->assertStatus(422);
});

it('opens the site and the API with a valid access cookie only', function () {
    $this->withUnencryptedCookie('dc_preview', previewCookie())
        ->get('/')
        ->assertOk()
        ->assertDontSee('name="password"', false)
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->withCredentials()->withUnencryptedCookie('dc_preview', previewCookie())->getJson('/api/v1/sections')->assertOk();

    $this->withCredentials()->withUnencryptedCookie('dc_preview', 'forged')->getJson('/api/v1/sections')->assertForbidden();
});

it('signs everyone out when the password changes', function () {
    $old = previewCookie();
    config(['preview.password' => 'new-password']);

    $this->withCredentials()->withUnencryptedCookie('dc_preview', $old)->getJson('/api/v1/sections')->assertForbidden();
});

it('stays locked when no credentials are configured', function () {
    config(['preview.username' => null, 'preview.password' => null]);

    $this->post('/preview/login', ['username' => '', 'password' => ''])->assertStatus(422);
});

it('keeps crawlers out while the gate is on', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /')->assertDontSee('Sitemap:');
});

it('changes nothing when the gate is off', function () {
    config(['preview.enabled' => false]);

    $this->get('/')->assertOk()->assertDontSee('name="password"', false);
    $this->getJson('/api/v1/sections')->assertOk();
});

it('sends people who signed in from the landing page to the app', function (string $next) {
    $this->post('/preview/login', ['username' => 'team', 'password' => 'launch-2026', 'next' => $next])
        ->assertRedirect(config('app.frontend_url').'/');
})->with(['/', '/?lang=en', '/en']);

it('explains a failed sign-in in the page language', function () {
    $this->post('/preview/login?lang=en', ['username' => 'team', 'password' => 'wrong'])
        ->assertStatus(422)
        ->assertSee('Those details are not right');
});
