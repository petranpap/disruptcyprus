<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('coming_soon.title') }}</title>
    <meta name="description" content="{{ __('coming_soon.description') }}">
    <meta property="og:title" content="{{ __('coming_soon.title') }}">
    <meta property="og:description" content="{{ __('coming_soon.description') }}">
    <meta property="og:image" content="{{ asset('site/og-default.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#0F172A">
    <link rel="icon" href="{{ asset('site/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('site/apple-touch-icon-180x180.png') }}">
    @foreach ($locale === 'el' ? ['noto-serif-display-greek', 'inter-greek', 'inter-latin'] : ['playfair-display-latin', 'inter-latin'] as $font)
        <link rel="preload" href="{{ asset("site/fonts/{$font}-wght-normal.woff2") }}" as="font" type="font/woff2" crossorigin>
    @endforeach
    <link rel="stylesheet" href="{{ asset('site/tokens.css') }}?v={{ filemtime(public_path('site/tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('site/coming-soon.css') }}?v={{ filemtime(public_path('site/coming-soon.css')) }}">
</head>
<body class="cs{{ $error ? ' cs-still' : '' }}">
    <div class="cs-sky" aria-hidden="true">
        <span class="cs-orb cs-orb-1"></span>
        <span class="cs-orb cs-orb-2"></span>
        <span class="cs-orb cs-orb-3"></span>
        <span class="cs-grid"></span>
    </div>

    <header class="cs-top">
        <span class="cs-logo" role="img" aria-label="Disrupt Cyprus">
            <svg viewBox="0 0 512 512" class="cs-mark" aria-hidden="true" focusable="false">
                <rect width="512" height="512" rx="112" class="cs-mark-bg"/>
                <g fill="#FFFFFF" transform="skewX(-12) translate(54 0)">
                    <rect class="cs-bar cs-bar-1" x="172" y="104" width="56" height="304" rx="16"/>
                    <rect class="cs-bar cs-bar-2" x="284" y="104" width="56" height="304" rx="16"/>
                    <rect class="cs-bar cs-bar-3" x="112" y="186" width="288" height="56" rx="16"/>
                    <rect class="cs-bar cs-bar-4" x="112" y="270" width="288" height="56" rx="16"/>
                </g>
            </svg>
            <span class="cs-word" aria-hidden="true"><span class="cs-crimson">Disrupt</span><span class="cs-cyan">Cyprus</span></span>
        </span>
        <a href="{{ $switchUrl }}" class="cs-lang" lang="{{ $locale === 'el' ? 'en' : 'el' }}" aria-label="{{ __('coming_soon.language_label') }}">{{ __('coming_soon.language') }}</a>
    </header>

    <main id="main" class="cs-main">
        <section class="cs-hero">
            <p class="cs-badge cs-rise" style="--d: 0.15s"><span class="cs-pulse" aria-hidden="true"></span>{{ __('coming_soon.badge') }}</p>
            <h1 class="cs-title cs-rise" style="--d: 0.3s">{{ __('coming_soon.headline') }}</h1>
            <p class="cs-subtitle cs-rise" style="--d: 0.45s">{{ __('coming_soon.subtitle') }}</p>

            <ul class="cs-features">
                @foreach (__('coming_soon.features') as $index => $feature)
                    <li class="cs-feature cs-rise" style="--d: {{ 0.6 + $index * 0.12 }}s">
                        <span class="cs-feature-icon">@include('site.partials.icon', ['name' => $feature['icon']])</span>
                        <span>
                            <span class="cs-feature-title">{{ $feature['title'] }}</span>
                            <span class="cs-feature-body">{{ $feature['body'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section id="login" class="cs-card cs-rise" style="--d: 0.5s" aria-labelledby="login-title">
            <span class="cs-card-icon">@include('site.partials.icon', ['name' => 'lock'])</span>
            <h2 id="login-title" class="cs-card-title">{{ __('coming_soon.login.title') }}</h2>
            <p class="cs-card-body">{{ __('coming_soon.login.body') }}</p>

            @if ($error)
                <p class="cs-error" role="alert">{{ $error }}</p>
            @endif

            {{-- #login brings the card (and any error) back into view after a failed attempt. --}}
            <form method="post" action="{{ $loginUrl }}#login" class="cs-form">
                <input type="hidden" name="next" value="{{ $next }}">
                <label class="cs-field">
                    <span>{{ __('coming_soon.login.username') }}</span>
                    <input type="text" name="username" value="{{ $username }}" autocomplete="username" autocapitalize="none" spellcheck="false" required>
                </label>
                <label class="cs-field">
                    <span>{{ __('coming_soon.login.password') }}</span>
                    <input type="password" name="password" autocomplete="current-password" required @if ($error) autofocus @endif>
                </label>
                <button type="submit" class="cs-button">{{ __('coming_soon.login.submit') }} <span aria-hidden="true">→</span></button>
            </form>
        </section>
    </main>

    @if ($industries !== [])
        <div class="cs-ticker" aria-label="{{ __('coming_soon.ticker') }}">
            <p class="cs-ticker-label">{{ __('coming_soon.ticker') }}</p>
            <div class="cs-ticker-track" aria-hidden="true">
                {{-- Rendered twice so the loop is seamless. --}}
                @foreach ([1, 2] as $copy)
                    <ul class="cs-ticker-list">
                        @foreach ($industries as $industry)
                            <li><span class="cs-dot" style="--industry: {{ $industry['color'] }}"></span>{{ $industry['name'] }}</li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    @endif

    <footer class="cs-footer">
        <span>{{ __('coming_soon.contact') }}</span>
        <span>{{ __('coming_soon.rights', ['year' => now()->year]) }}</span>
    </footer>
</body>
</html>
