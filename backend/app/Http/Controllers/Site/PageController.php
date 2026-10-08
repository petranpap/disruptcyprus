<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Site\PageMeta;
use App\Support\Site\SiteLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * About, contact and the legal pages. Content lives in resources/views/site/pages/{page}-{locale}.blade.php.
 */
class PageController extends Controller
{
    public const PAGES = ['about', 'contact', 'privacy', 'terms'];

    /** Date shown on the legal pages; bump when their text changes. */
    public const LEGAL_UPDATED = '2026-10-08';

    public function __invoke(Request $request, string $page): View
    {
        $locale = SiteLocale::fromRequest($request);
        app()->setLocale($locale);

        $meta = new PageMeta(
            title: __("site.pages.{$page}"),
            description: __('site.meta.description'),
            canonical: SiteLocale::url('/'.$page, $locale),
            locale: $locale,
            alternates: ['el' => SiteLocale::url('/'.$page, 'el'), 'en' => SiteLocale::url('/'.$page, 'en')],
        );

        return view('site.page', [
            'meta' => $meta,
            'locale' => $locale,
            'page' => $page,
            'content' => "site.pages.{$page}-{$locale}",
            'updated' => in_array($page, ['privacy', 'terms'], true) ? self::LEGAL_UPDATED : null,
        ]);
    }
}
