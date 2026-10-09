@php
    /** @var \App\Support\Site\PageMeta $meta */
    $otherLocale = $locale === 'el' ? 'en' : 'el';
    $switchUrl = $meta->alternates[$otherLocale] ?? null;
    $pageUrl = fn (string $page) => \App\Support\Site\SiteLocale::url('/'.$page, $locale);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $meta->fullTitle() }}</title>
    <meta name="description" content="{{ $meta->description }}">
    <link rel="canonical" href="{{ $meta->canonical }}">
    @foreach ($meta->alternates as $hreflang => $href)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
    @endforeach
    @if (isset($meta->alternates['el']))
        <link rel="alternate" hreflang="x-default" href="{{ $meta->alternates['el'] }}">
    @endif

    <meta property="og:site_name" content="Disrupt Cyprus">
    <meta property="og:type" content="{{ $meta->type }}">
    <meta property="og:title" content="{{ $meta->title }}">
    <meta property="og:description" content="{{ $meta->description }}">
    <meta property="og:url" content="{{ $meta->canonical }}">
    <meta property="og:image" content="{{ $meta->imageUrl() }}">
    <meta property="og:locale" content="{{ $locale === 'el' ? 'el_GR' : 'en_GB' }}">
    @if ($meta->publishedAt)
        <meta property="article:published_time" content="{{ $meta->publishedAt }}">
        <meta property="article:modified_time" content="{{ $meta->modifiedAt }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta->title }}">
    <meta name="twitter:description" content="{{ $meta->description }}">
    <meta name="twitter:image" content="{{ $meta->imageUrl() }}">

    <meta name="theme-color" content="#FAFAF8" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#121316" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('site/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('site/apple-touch-icon-180x180.png') }}">
    {{-- The fonts above the fold in this language: preloading them avoids the headline reflowing when they swap in. --}}
    @foreach ($locale === 'el'
        ? ['noto-serif-display-greek', 'source-serif-4-greek', 'inter-greek', 'inter-latin']
        : ['playfair-display-latin', 'source-serif-4-latin', 'inter-latin'] as $font)
        <link rel="preload" href="{{ asset("site/fonts/{$font}-wght-normal.woff2") }}" as="font" type="font/woff2" crossorigin>
    @endforeach
    <link rel="stylesheet" href="{{ asset('site/tokens.css') }}?v={{ filemtime(public_path('site/tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('site/site.css') }}?v={{ filemtime(public_path('site/site.css')) }}">
    @if ($jsonLd = $meta->jsonLdScript())
        <script type="application/ld+json">{!! $jsonLd !!}</script>
    @endif
</head>
<body>
    <a class="skip" href="#main">{{ __('site.nav.skip') }}</a>
    @if (\App\Support\Preview\PreviewAccess::enabled())
        {{-- Only staff get past the pre-launch gate: remind them what everyone else sees (like WordPress maintenance mode). --}}
        <div class="preview-bar" role="status">
            <span><span class="preview-dot" aria-hidden="true"></span>{{ __('site.preview.notice') }}</span>
            <a href="{{ url('/admin') }}">{{ __('site.preview.admin') }}</a>
        </div>
    @endif
    <header class="site-header">
        <div class="container header-row">
            <a href="{{ $locale === 'el' ? url('/') : url('/en') }}" class="header-logo">@include('site.partials.logo')</a>
            <nav aria-label="{{ __('site.nav.main') }}" class="header-actions">
                @if ($switchUrl)
                    <a href="{{ $switchUrl }}" hreflang="{{ $otherLocale }}" lang="{{ $otherLocale }}" class="link-quiet" aria-label="{{ __('site.nav.language_label') }}">{{ __('site.nav.language') }}</a>
                @endif
                <a href="{{ \App\Support\Site\SiteLocale::appUrl('/') }}" class="btn btn-primary btn-sm">{{ __('site.nav.open_app') }}</a>
            </nav>
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <a href="{{ $locale === 'el' ? url('/') : url('/en') }}">@include('site.partials.logo')</a>
                <p class="footer-tagline">{{ __('site.footer.tagline') }}</p>
            </div>
            <ul class="footer-links">
                <li><a href="{{ $pageUrl('about') }}">{{ __('site.footer.about') }}</a></li>
                <li><a href="{{ $pageUrl('contact') }}">{{ __('site.footer.contact') }}</a></li>
                <li><a href="{{ $pageUrl('privacy') }}">{{ __('site.footer.privacy') }}</a></li>
                <li><a href="{{ $pageUrl('terms') }}">{{ __('site.footer.terms') }}</a></li>
            </ul>
        </div>
        <div class="container footer-bottom">
            <span>{{ __('site.footer.rights', ['year' => now()->year]) }}</span>
            <span>{{ __('site.footer.made_in') }}</span>
        </div>
    </footer>
</body>
</html>
