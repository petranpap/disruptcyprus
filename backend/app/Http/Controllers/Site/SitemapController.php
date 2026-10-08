<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Digest;
use App\Models\Event;
use App\Support\Site\SiteLocale;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /** Search engines don't need to-the-minute freshness; publishing more than hourly is rare. */
    private const CACHE_SECONDS = 3600;

    public function sitemap(): Response
    {
        $xml = Cache::remember('site:sitemap', self::CACHE_SECONDS, fn (): string => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /api/', '', 'Sitemap: '.url('/sitemap.xml')];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function build(): string
    {
        $entries = [
            $this->entry(['el' => url('/'), 'en' => url('/en')], null),
        ];
        foreach (PageController::PAGES as $page) {
            $entries[] = $this->entry(['el' => SiteLocale::url('/'.$page, 'el'), 'en' => SiteLocale::url('/'.$page, 'en')], null);
        }

        Article::query()->published()->select(['id', 'slug', 'available_locales', 'updated_at'])->orderBy('id')
            ->each(function (Article $article) use (&$entries): void {
                $entries[] = $this->entry($this->localized('/a/'.$article->slug, $article->available_locales), $article->updated_at?->toAtomString());
            });
        Event::query()->published()->select(['id', 'slug', 'available_locales', 'updated_at'])->orderBy('id')
            ->each(function (Event $event) use (&$entries): void {
                $entries[] = $this->entry($this->localized('/e/'.$event->slug, $event->available_locales), $event->updated_at?->toAtomString());
            });
        Digest::query()->published()->select(['id', 'slug', 'updated_at'])->orderBy('id')
            ->each(function (Digest $digest) use (&$entries): void {
                $entries[] = $this->entry($this->localized('/d/'.$digest->slug, SiteLocale::SUPPORTED), $digest->updated_at?->toAtomString());
            });

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n"
            .implode("\n", $entries)."\n</urlset>\n";
    }

    /**
     * @param  list<string>  $locales
     * @return array<string, string>
     */
    private function localized(string $path, array $locales): array
    {
        $urls = [];
        foreach (array_intersect(SiteLocale::SUPPORTED, $locales) as $locale) {
            $urls[$locale] = SiteLocale::url($path, $locale);
        }

        return $urls;
    }

    /**
     * One <url> per language version, each listing every version as an alternate (Google's hreflang format).
     *
     * @param  array<string, string>  $urls
     */
    private function entry(array $urls, ?string $lastModified): string
    {
        $alternates = count($urls) > 1
            ? implode('', array_map(fn (string $locale, string $url) => sprintf('<xhtml:link rel="alternate" hreflang="%s" href="%s"/>', $locale, e($url)), array_keys($urls), $urls))
            : '';
        $lastmod = $lastModified !== null ? '<lastmod>'.$lastModified.'</lastmod>' : '';

        return implode("\n", array_map(
            fn (string $url) => '  <url><loc>'.e($url).'</loc>'.$lastmod.$alternates.'</url>',
            array_values($urls),
        ));
    }
}
