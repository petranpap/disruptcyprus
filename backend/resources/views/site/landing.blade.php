@extends('site.layout')

@section('content')
    <section class="hero">
        <div class="container hero-inner">
            <p class="edition"><span class="dot" aria-hidden="true"></span>{{ __('site.landing.edition') }}<span class="edition-tagline">{{ __('site.landing.tagline') }}</span></p>
            <h1 class="hero-title">{{ __('site.landing.headline') }}</h1>
            <p class="hero-subtitle">{{ __('site.landing.subtitle') }}</p>
            <div class="hero-actions">
                <a href="{{ \App\Support\Site\SiteLocale::appUrl('/sign-up') }}" class="btn btn-primary">{{ __('site.landing.create_account') }}</a>
                <a href="{{ \App\Support\Site\SiteLocale::appUrl('/') }}" class="btn btn-outline">{{ __('site.landing.browse') }}</a>
            </div>
            <p class="hint">{{ __('site.landing.install_hint') }}</p>
        </div>
    </section>

    <section class="container features" aria-label="Disrupt Cyprus">
        @foreach (__('site.landing.features') as $feature)
            <div class="feature">
                <h2 class="feature-title">{{ $feature['title'] }}</h2>
                <p>{{ $feature['body'] }}</p>
            </div>
        @endforeach
    </section>

    @if ($articles->isNotEmpty())
        <section class="container section" aria-labelledby="latest-title">
            <div class="section-head">
                <h2 id="latest-title" class="section-title">{{ __('site.landing.latest') }}</h2>
                <a href="{{ \App\Support\Site\SiteLocale::appUrl('/') }}" class="link-arrow">{{ __('site.landing.all_stories') }} →</a>
            </div>
            <div class="card-grid">
                @foreach ($articles as $article)
                    @include('site.partials.article-card', ['article' => $article])
                @endforeach
            </div>
        </section>
    @endif

    <section class="container section split">
        @if ($events->isNotEmpty())
            <div aria-labelledby="events-title">
                <div class="section-head">
                    <h2 id="events-title" class="section-title">{{ __('site.landing.upcoming') }}</h2>
                    <a href="{{ \App\Support\Site\SiteLocale::appUrl('/events') }}" class="link-arrow">{{ __('site.landing.all_events') }} →</a>
                </div>
                <ul class="event-list">
                    @foreach ($events as $event)
                        @include('site.partials.event-row', ['event' => $event])
                    @endforeach
                </ul>
            </div>
        @endif
        @if ($digest)
            <div aria-labelledby="digest-title">
                <h2 id="digest-title" class="section-title">{{ __('site.landing.digest') }}</h2>
                <a href="{{ \App\Support\Site\SiteLocale::url('/d/'.$digest->slug, $locale) }}" class="briefing">
                    <span class="kicker">{{ $digest->published_at?->locale($locale)->isoFormat('LL') }}</span>
                    <span class="card-title">{{ $digest->getTranslation('title', $locale) }}</span>
                    @if ($intro = $digest->getTranslation('intro', $locale))
                        <span class="briefing-intro">{{ \Illuminate\Support\Str::limit($intro, 180) }}</span>
                    @endif
                </a>
            </div>
        @endif
    </section>

    <section class="container editorial">
        <p>{{ __('site.landing.editorial') }}</p>
    </section>
@endsection
