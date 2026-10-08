@php
    $cardTitle = $article->getTranslation('title', $locale, false);
    $cardIndustry = $article->primaryIndustry();
    $cardImage = $article->getFirstMedia(\App\Models\Article::HERO_COLLECTION);
@endphp
<article class="card">
    <a href="{{ \App\Support\Site\SiteLocale::url('/a/'.$article->slug, $locale) }}" class="card-link">
        @if ($cardImage)
            <img src="{{ $cardImage->getAvailableUrl(['card']) }}" alt="" class="card-image" loading="lazy" width="800" height="500">
        @endif
        <span class="card-body">
            @if ($cardIndustry)
                <span class="kicker" style="--industry: {{ $cardIndustry->color }}">{{ $cardIndustry->getTranslation('name', $locale) }}</span>
            @endif
            <span class="card-title">{{ $cardTitle }}</span>
            <span class="meta"><time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->locale($locale)->isoFormat('LL') }}</time></span>
        </span>
    </a>
</article>
