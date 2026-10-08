<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Digest;
use App\Models\DigestItem;
use App\Models\Event;
use App\Services\Content\HtmlSanitizer;
use App\Support\Site\PageMeta;
use App\Support\Site\SiteLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Public, indexable pages behind every shared link (/a, /e, /d): full text, social previews, structured data,
 * and a way into the app. Unpublished content is a 404, exactly like the API.
 */
class ShareController extends Controller
{
    public function article(Request $request, string $slug, HtmlSanitizer $sanitizer): View
    {
        $article = Article::query()->published()->where('slug', $slug)
            ->with(['author.media', 'industries', 'section', 'media'])
            ->firstOrFail();
        $locale = $this->contentLocale($request, $article);

        $body = Cache::remember(
            sprintf('article-body:%d:%d:%s', $article->id, $article->updated_at?->getTimestamp() ?? 0, $locale),
            now()->addDay(),
            fn () => $sanitizer->sanitize($article->getTranslation('body', $locale, false) ?: null),
        );
        $title = (string) $article->getTranslation('title', $locale, false);
        $excerpt = (string) $article->getTranslation('excerpt', $locale, false);
        $hero = $article->getFirstMedia(Article::HERO_COLLECTION);
        $image = SiteLocale::absolute($hero?->getAvailableUrl(['hero']));

        $meta = new PageMeta(
            title: $title,
            description: Str::limit($excerpt ?: strip_tags($body), 200),
            canonical: SiteLocale::url('/a/'.$article->slug, $locale),
            locale: $locale,
            image: $image,
            type: 'article',
            alternates: $this->alternates('/a/'.$article->slug, $article->available_locales),
            publishedAt: $article->published_at?->toIso8601String(),
            modifiedAt: $article->updated_at?->toIso8601String(),
            jsonLd: [
                '@type' => 'NewsArticle',
                'headline' => Str::limit($title, 110, ''),
                'description' => $excerpt,
                'image' => $image !== null ? [$image] : [asset('site/og-default.png')],
                'datePublished' => $article->published_at?->toIso8601String(),
                'dateModified' => $article->updated_at?->toIso8601String(),
                'inLanguage' => $locale,
                'author' => ['@type' => 'Person', 'name' => $article->author->name],
                'publisher' => ['@type' => 'Organization', 'name' => 'Disrupt Cyprus', 'logo' => ['@type' => 'ImageObject', 'url' => asset('site/logo-512.png')]],
                'mainEntityOfPage' => SiteLocale::url('/a/'.$article->slug, $locale),
            ],
        );

        app()->setLocale($locale);

        return view('site.article', [
            'meta' => $meta,
            'article' => $article,
            'locale' => $locale,
            'title' => $title,
            'excerpt' => $excerpt,
            'body' => $body,
            'hero' => $hero,
            'heroCaption' => $article->getTranslation('hero_caption', $locale, false) ?: null,
            'industry' => $article->primaryIndustry(),
            'attachment' => $article->getFirstMedia(Article::ATTACHMENT_COLLECTION),
            'appUrl' => SiteLocale::appUrl('/articles/'.$article->slug),
        ]);
    }

    public function event(Request $request, string $slug, HtmlSanitizer $sanitizer): View
    {
        $event = Event::query()->published()->where('slug', $slug)->with(['industries', 'media'])->firstOrFail();
        $locale = $this->contentLocale($request, $event);

        $title = (string) $event->getTranslation('title', $locale, false);
        $description = (string) $event->getTranslation('description', $locale, false);
        $image = SiteLocale::absolute($event->getFirstMedia(Event::HERO_COLLECTION)?->getAvailableUrl(['hero']));
        $location = $event->is_online
            ? ['@type' => 'VirtualLocation', 'url' => $event->online_url ?: SiteLocale::appUrl('/events/'.$event->slug)]
            : ['@type' => 'Place', 'name' => $event->location_name ?: $event->city, 'address' => trim(implode(', ', array_filter([$event->address, $event->city, 'Cyprus'])))];

        $meta = new PageMeta(
            title: $title,
            description: Str::limit(strip_tags($description), 200),
            canonical: SiteLocale::url('/e/'.$event->slug, $locale),
            locale: $locale,
            image: $image,
            alternates: $this->alternates('/e/'.$event->slug, $event->available_locales),
            jsonLd: array_filter([
                '@type' => 'Event',
                'name' => $title,
                'description' => Str::limit(strip_tags($description), 500),
                'startDate' => $event->starts_at->copy()->setTimezone($event->timezone)->toIso8601String(),
                'endDate' => $event->ends_at?->copy()->setTimezone($event->timezone)->toIso8601String(),
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => $event->is_online ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode',
                'location' => $location,
                'image' => $image !== null ? [$image] : null,
                'organizer' => $event->organizer_name ? ['@type' => 'Organization', 'name' => $event->organizer_name] : null,
                'url' => SiteLocale::url('/e/'.$event->slug, $locale),
            ]),
        );

        app()->setLocale($locale);

        return view('site.event', [
            'meta' => $meta,
            'event' => $event,
            'locale' => $locale,
            'title' => $title,
            'descriptionHtml' => $sanitizer->sanitize($description ?: null),
            'image' => $image,
            'price' => $event->getTranslation('price_info', $locale, false) ?: null,
            'venue' => $this->venue($event),
            'industry' => $event->primaryIndustry(),
            'appUrl' => SiteLocale::appUrl('/events/'.$event->slug),
        ]);
    }

    public function digest(Request $request, string $slug): View
    {
        $digest = Digest::query()->published()->where('slug', $slug)
            ->with(['items' => fn ($items) => $items->orderBy('position'), 'items.itemable.media'])
            ->firstOrFail();
        $locale = SiteLocale::fromRequest($request);
        app()->setLocale($locale);

        $items = $digest->items
            ->filter(fn (DigestItem $item) => ($item->itemable instanceof Article || $item->itemable instanceof Event) && $item->itemable->isPublished())
            ->values();
        $title = (string) $digest->getTranslation('title', $locale);
        $intro = (string) $digest->getTranslation('intro', $locale);

        $meta = new PageMeta(
            title: $title,
            description: Str::limit($intro ?: __('site.meta.description'), 200),
            canonical: SiteLocale::url('/d/'.$digest->slug, $locale),
            locale: $locale,
            alternates: $this->alternates('/d/'.$digest->slug, SiteLocale::SUPPORTED),
        );

        return view('site.digest', [
            'meta' => $meta,
            'digest' => $digest,
            'items' => $items,
            'locale' => $locale,
            'title' => $title,
            'intro' => $intro,
            'appUrl' => SiteLocale::appUrl('/digests/'.$digest->slug),
        ]);
    }

    /**
     * "Venue, street, city" without repeating parts that editors already typed into another field.
     */
    private function venue(Event $event): string
    {
        $parts = [];
        foreach ([$event->location_name, $event->address, $event->city] as $part) {
            $part = trim((string) $part);
            $repeated = collect($parts)->contains(fn (string $kept) => Str::contains($kept, $part, ignoreCase: true));
            if ($part !== '' && ! $repeated) {
                $parts = array_values(array_filter($parts, fn (string $kept) => ! Str::contains($part, $kept, ignoreCase: true)));
                $parts[] = $part;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * The requested language when the content exists in it, otherwise the language it was written in.
     */
    private function contentLocale(Request $request, Article|Event $content): string
    {
        $requested = SiteLocale::fromRequest($request);

        return $content->resolveLocale($requested, [SiteLocale::other($requested)]) ?? $requested;
    }

    /**
     * @param  list<string>  $locales
     * @return array<string, string>
     */
    private function alternates(string $path, array $locales): array
    {
        $alternates = [];
        foreach (array_intersect(SiteLocale::SUPPORTED, $locales) as $locale) {
            $alternates[$locale] = SiteLocale::url($path, $locale);
        }

        return $alternates;
    }
}
