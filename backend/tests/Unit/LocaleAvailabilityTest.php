<?php

use App\Models\Article;
use App\Models\Event;

it('marks a locale available only when title and body exist', function () {
    $article = new Article;
    $article->setTranslations('title', ['en' => 'Title', 'el' => 'Τίτλος']);
    $article->setTranslations('body', ['en' => '<p>Body</p>', 'el' => '<p> </p>']);

    expect($article->computeAvailableLocales())->toBe(['en']);
});

it('resolves the best locale for a reader', function () {
    $article = (new Article)->forceFill(['available_locales' => ['en']]);

    expect($article->resolveLocale('el', ['el', 'en']))->toBe('en')
        ->and($article->resolveLocale('el', ['el']))->toBeNull()
        ->and($article->resolveLocale('en', ['el']))->toBe('en');
});

it('requires a description for events', function () {
    $event = new Event;
    $event->setTranslations('title', ['en' => 'Pitch Night', 'el' => 'Βραδιά Pitch']);
    $event->setTranslations('description', ['el' => 'Περιγραφή']);

    expect($event->computeAvailableLocales())->toBe(['el']);
});
