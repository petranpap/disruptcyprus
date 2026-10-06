{{-- Reader-like preview of both languages, rendered through the same sanitizer as the API. --}}
<div class="space-y-10">
    @foreach (['el', 'en'] as $locale)
        @php($title = $article->getTranslation('title', $locale, false))
        @continue(blank($title))
        <article lang="{{ $locale }}" class="space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('admin.locales.'.$locale) }}</p>
            <h2 class="text-2xl font-bold" style="font-family: 'Playfair Display', 'Noto Serif Display', serif">{{ $title }}</h2>
            @if ($excerpt = $article->getTranslation('excerpt', $locale, false))
                <p class="text-gray-600 dark:text-gray-300">{{ $excerpt }}</p>
            @endif
            @if ($image = $article->getFirstMedia(\App\Models\Article::HERO_COLLECTION))
                <img src="{{ $image->getAvailableUrl(['card']) }}" alt="" class="w-full rounded-xl">
            @endif
            <div class="prose max-w-none dark:prose-invert">{!! $sanitizer->sanitize($article->getTranslation('body', $locale, false)) !!}</div>
        </article>
    @endforeach
</div>
