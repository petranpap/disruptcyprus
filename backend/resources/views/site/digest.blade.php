@extends('site.layout')

@section('content')
    <article class="reading">
        <header class="container-reading article-header">
            <p class="kickers"><span class="chip chip-original">{{ $digest->published_at?->locale($locale)->isoFormat('LL') }}</span></p>
            <h1 class="article-title">{{ $title }}</h1>
            @if ($intro)
                <p class="article-excerpt">{{ $intro }}</p>
            @endif
            <p class="byline"><span>{{ trans_choice('site.digest.items', $items->count(), ['count' => $items->count()]) }}</span></p>
        </header>

        <ol class="container-reading digest-items">
            @foreach ($items as $item)
                @php
                    $content = $item->itemable;
                    $isArticle = $content instanceof \App\Models\Article;
                    $itemLocale = $content->resolveLocale($locale, [$locale === 'el' ? 'en' : 'el']) ?? $locale;
                    $note = $item->getTranslation('editor_note', $locale, false);
                @endphp
                <li class="digest-item">
                    <a href="{{ \App\Support\Site\SiteLocale::url(($isArticle ? '/a/' : '/e/').$content->slug, $locale) }}">
                        <span class="card-title">{{ $content->getTranslation('title', $itemLocale, false) }}</span>
                    </a>
                    @if ($note)
                        <p class="digest-note">{{ $note }}</p>
                    @elseif ($isArticle && ($excerpt = $content->getTranslation('excerpt', $itemLocale, false)))
                        <p class="digest-note">{{ $excerpt }}</p>
                    @endif
                </li>
            @endforeach
        </ol>

        <div class="container-reading">
            @include('site.partials.app-cta', [
                'title' => __('site.article.cta_title_generic'),
                'url' => $appUrl,
                'action' => __('site.digest.open'),
            ])
        </div>
    </article>
@endsection
