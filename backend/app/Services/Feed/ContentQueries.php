<?php

namespace App\Services\Feed;

use App\Models\Article;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared base queries: published, readable in the given locales, with card relations loaded.
 */
final class ContentQueries
{
    public const ARTICLE_CARD_RELATIONS = ['section', 'author', 'industries', 'media'];

    public const EVENT_CARD_RELATIONS = ['industries', 'media'];

    /**
     * @param  list<string>  $locales
     * @return Builder<Article>
     */
    public static function articles(array $locales): Builder
    {
        return Article::query()
            ->published()
            ->availableInAny($locales)
            ->with(self::ARTICLE_CARD_RELATIONS);
    }

    /**
     * @param  list<string>  $locales
     * @return Builder<Event>
     */
    public static function events(array $locales): Builder
    {
        return Event::query()
            ->published()
            ->availableInAny($locales)
            ->with(self::EVENT_CARD_RELATIONS);
    }

    /**
     * @template TModel of Article|Event
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $industrySlugs
     * @return Builder<TModel>
     */
    public static function inIndustries(Builder $query, array $industrySlugs): Builder
    {
        return $industrySlugs === []
            ? $query
            : $query->whereHas('industries', fn (Builder $industries) => $industries->whereIn('slug', $industrySlugs));
    }
}
