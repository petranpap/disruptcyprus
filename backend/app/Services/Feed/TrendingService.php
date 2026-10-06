<?php

namespace App\Services\Feed;

use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Trending articles: views + 3 × saves over the last 48 hours. When the window is quiet the list is
 * topped up with the most-read recent articles so the carousel never looks empty.
 */
class TrendingService
{
    public const WINDOW_HOURS = 48;

    public const BOOKMARK_WEIGHT = 3;

    public const FILL_DAYS = 14;

    public const CACHE_SECONDS = 300;

    /**
     * @param  list<string>  $locales
     * @return Collection<int, Article>
     */
    public function articles(array $locales, int $limit = 10): Collection
    {
        $ids = Cache::remember(
            'trending:'.implode(',', $locales).':'.$limit,
            self::CACHE_SECONDS,
            fn () => $this->rankedIds($locales, $limit),
        );

        $articles = ContentQueries::articles($locales)->whereKey($ids)->get()->keyBy('id');

        return collect($ids)->map(fn (int $id) => $articles->get($id))->filter()->values();
    }

    /**
     * @param  list<string>  $locales
     * @return list<int>
     */
    private function rankedIds(array $locales, int $limit): array
    {
        $since = now()->subHours(self::WINDOW_HOURS);
        $morph = (new Article)->getMorphClass();

        $views = DB::table('content_view_stats')
            ->selectRaw('viewable_id as article_id, SUM(views) as score')
            ->where('viewable_type', $morph)
            ->where('bucket_at', '>=', $since)
            ->groupBy('viewable_id');

        $saves = DB::table('bookmarks')
            ->selectRaw('bookmarkable_id as article_id, COUNT(*) * ? as score', [self::BOOKMARK_WEIGHT])
            ->where('bookmarkable_type', $morph)
            ->where('created_at', '>=', $since)
            ->groupBy('bookmarkable_id');

        $scores = DB::query()
            ->fromSub($views->unionAll($saves), 'signals')
            ->selectRaw('article_id, SUM(score) as total')
            ->groupBy('article_id');

        /** @var list<int> $trending */
        $trending = Article::query()
            ->published()
            ->availableInAny($locales)
            ->joinSub($scores, 'scores', 'scores.article_id', '=', 'articles.id')
            ->orderByDesc('scores.total')
            ->orderByDesc('articles.published_at')
            ->limit($limit)
            ->pluck('articles.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($trending) >= $limit) {
            return $trending;
        }

        $fill = Article::query()
            ->published()
            ->availableInAny($locales)
            ->whereKeyNot($trending)
            ->where('published_at', '>=', now()->subDays(self::FILL_DAYS))
            ->orderByDesc('view_count')
            ->limit($limit - count($trending))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [...$trending, ...$fill];
    }
}
