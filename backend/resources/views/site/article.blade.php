@extends('site.layout')

@section('content')
    <article class="reading">
        <header class="container-reading article-header">
            <p class="kickers">
                @if ($industry)
                    <span class="chip" style="--industry: {{ $industry->color }}">{{ $industry->getTranslation('name', $locale) }}</span>
                @endif
                @if ($article->is_original)
                    <span class="chip chip-original">{{ __('site.article.original') }}</span>
                @endif
            </p>
            <h1 class="article-title">{{ $title }}</h1>
            @if ($excerpt)
                <p class="article-excerpt">{{ $excerpt }}</p>
            @endif
            <p class="byline">
                <span class="byline-name">{{ $article->author->name }}</span>
                @if ($article->author->title)
                    <span>{{ $article->author->title }}</span>
                @endif
                <span><time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->locale($locale)->isoFormat('LL') }}</time></span>
                @if ($minutes = $article->reading_time_minutes[$locale] ?? null)
                    <span>{{ trans_choice('site.article.reading_time', $minutes, ['minutes' => $minutes]) }}</span>
                @endif
            </p>
        </header>

        @if ($hero)
            <figure class="container-wide hero-figure">
                <img src="{{ $hero->getAvailableUrl(['hero']) }}" alt="{{ $heroCaption ?? '' }}" width="1600" height="900" fetchpriority="high">
                @if ($heroCaption)
                    <figcaption>{{ $heroCaption }}</figcaption>
                @endif
            </figure>
        @endif

        <div class="container-reading prose" lang="{{ $locale }}">
            {!! $body !!}
        </div>

        @if ($attachment)
            <p class="container-reading">
                <a href="{{ $attachment->getUrl() }}" class="btn btn-outline">{{ __('site.article.attachment') }} ({{ $attachment->human_readable_size }})</a>
            </p>
        @endif

        <div class="container-reading">
            @include('site.partials.app-cta', [
                'title' => $industry ? __('site.article.cta_title', ['industry' => $industry->getTranslation('name', $locale)]) : __('site.article.cta_title_generic'),
                'url' => $appUrl,
                'action' => __('site.article.open'),
            ])
        </div>
    </article>
@endsection
