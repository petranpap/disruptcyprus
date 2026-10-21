<?php

/**
 * A server .env copied from .env.example still says localhost: links must follow the real host anyway.
 */
beforeEach(function () {
    config(['app.url' => 'http://localhost:8080', 'app.frontend_url' => 'http://localhost:5173']);
});

it('derives share links and the app link from the host the request arrived on', function () {
    $article = newArticle();

    $this->getJson('https://app.example.org/api/v1/articles/'.$article->slug)
        ->assertOk()
        ->assertJsonPath('data.share_url', 'https://example.org/a/'.$article->slug);

    $this->get('https://example.org/')
        ->assertOk()
        ->assertSee('https://app.example.org/', false)
        ->assertDontSee('localhost', false);
});

it('accepts cookie sign-in from the app host without SANCTUM_STATEFUL_DOMAINS listing it', function () {
    reader(['email' => 'anna@example.org']);

    $this->withHeaders(['Origin' => 'https://app.example.org', 'Referer' => 'https://app.example.org/sign-in'])
        ->postJson('https://app.example.org/api/v1/auth/login', ['email' => 'anna@example.org', 'password' => 'password'])
        ->assertOk();

    $this->assertAuthenticated('web');
});
