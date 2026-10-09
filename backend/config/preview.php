<?php

/*
| Pre-launch gate. While enabled, the public site shows the "coming soon" page and the API answers 403
| `preview_locked`, except for people who signed in with the shared team credentials below.
| Turn it off on launch day: PREVIEW_ENABLED=false (then `php artisan optimize`).
*/
return [
    'enabled' => (bool) env('PREVIEW_ENABLED', false),

    // Shared team login. Set them in .env only; never commit real values.
    'username' => env('PREVIEW_USERNAME'),
    'password' => env('PREVIEW_PASSWORD'),

    'cookie' => 'dc_preview',

    // ".disruptcyprus.com" in production, so the main site and app.disruptcyprus.com share the access cookie.
    'cookie_domain' => env('PREVIEW_COOKIE_DOMAIN'),

    'lifetime_days' => (int) env('PREVIEW_LIFETIME_DAYS', 30),
];
