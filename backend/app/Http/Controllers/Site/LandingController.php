<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Digest;
use App\Models\Event;
use App\Support\Site\PageMeta;
use App\Support\Site\SiteLocale;
use Illuminate\Contracts\View\View;

/**
 * Marketing landing page: Greek at "/", English at "/en".
 */
class LandingController extends Controller
{
    public function __invoke(string $locale = SiteLocale::DEFAULT): View
    {
        app()->setLocale($locale);
        $path = fn (string $lang) => $lang === SiteLocale::DEFAULT ? url('/') : url('/en');

        $articles = Article::query()
            ->published()
            ->availableInAny([$locale])
            ->with(['industries', 'media'])
            ->latest('published_at')
            ->limit(6)
            ->get();

        $events = Event::query()
            ->published()
            ->upcoming()
            ->availableInAny([$locale])
            ->with('media')
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        $digest = Digest::query()->published()->latest('published_at')->first();

        $meta = new PageMeta(
            title: __('site.meta.title_suffix'),
            description: __('site.meta.description'),
            canonical: $path($locale),
            locale: $locale,
            alternates: ['el' => $path('el'), 'en' => $path('en')],
            jsonLd: [
                '@type' => 'WebSite',
                'name' => 'Disrupt Cyprus',
                'url' => url('/'),
                'inLanguage' => $locale,
                'publisher' => ['@type' => 'Organization', 'name' => 'Disrupt Cyprus', 'logo' => asset('site/logo-512.png')],
            ],
        );

        return view('site.landing', compact('meta', 'articles', 'events', 'digest', 'locale'));
    }
}
