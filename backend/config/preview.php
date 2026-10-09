<?php

/*
| Pre-launch gate. While enabled, the public site shows the "coming soon" page with the waitlist form and the API
| answers 403 `preview_locked`. Staff who sign in to /admin automatically get a personal access cookie (no shared
| password). Turn it off on launch day: PREVIEW_ENABLED=false (then `php artisan optimize`).
*/
return [
    'enabled' => (bool) env('PREVIEW_ENABLED', false),

    'cookie' => 'dc_preview',

    // ".disruptcyprus.com" in production, so the main site and app.disruptcyprus.com share the access cookie.
    'cookie_domain' => env('PREVIEW_COOKIE_DOMAIN'),

    'lifetime_days' => (int) env('PREVIEW_LIFETIME_DAYS', 14),
];
