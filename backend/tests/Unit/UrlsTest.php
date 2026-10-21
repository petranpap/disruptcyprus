<?php

use App\Support\Urls;

it('uses the configured origins when they are real', function () {
    config(['app.url' => 'https://disruptcyprus.com/', 'app.frontend_url' => 'https://app.disruptcyprus.com']);

    expect(Urls::site())->toBe('https://disruptcyprus.com')
        ->and(Urls::app())->toBe('https://app.disruptcyprus.com');
});

it('derives the app origin from the site when FRONTEND_URL is not set', function () {
    config(['app.url' => 'https://disruptcyprus.com', 'app.frontend_url' => null]);

    expect(Urls::app())->toBe('https://app.disruptcyprus.com')
        ->and(Urls::sharedCookieDomain())->toBe('.disruptcyprus.com');
});

it('keeps localhost origins for local development', function () {
    config(['app.url' => 'http://localhost:8080', 'app.frontend_url' => 'http://localhost:5173']);

    expect(Urls::site())->toBe('http://localhost:8080')
        ->and(Urls::app())->toBe('http://localhost:5173')
        ->and(Urls::sharedCookieDomain())->toBeNull();
});
