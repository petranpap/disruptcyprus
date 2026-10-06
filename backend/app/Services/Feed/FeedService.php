<?php

namespace App\Services\Feed;

use App\Models\Article;
use App\Models\Event;
use App\Models\Industry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * All feed ranking lives here.
 *
 * For you: published articles (last 30 days) and events starting within 14 days, in the reader's
 * followed industries and content languages, scored by recency decay with boosts for featured,
 * Disrupt Originals and multiple matching industries, then interleaved so one section never fills
 * more than three consecutive slots. Readers without industries get trending instead.
 */
class FeedService
{
    public const POOL_DAYS = 30;

    public const POOL_LIMIT = 300;

    public const EVENT_HORIZON_DAYS = 14;

    public const ARTICLE_HALF_LIFE_HOURS = 36;

    public const EVENT_HALF_LIFE_HOURS = 96;

    public const EVENT_BASE_WEIGHT = 0.6;

    public const FEATURED_BOOST = 0.25;

    public const ORIGINAL_BOOST = 0.15;

    public const EXTRA_INDUSTRY_BOOST = 0.1;

    public const MAX_EXTRA_INDUSTRIES = 3;

    public const MAX_SAME_SECTION_RUN = 3;

    public const EVENTS_SECTION = 'events';

    public const INDUSTRY_FEED_EVENT_SLOTS = [1, 4, 7];

    public function __construct(private readonly TrendingService $trending) {}

    /**
     * @param  list<string>  $locales
     */
    public function forYou(User $user, array $locales, FeedCursor $cursor, int $perPage = 20): FeedPage
    {
        $industryIds = $user->industries()->pluck('industries.id')->map(fn ($id) => (int) $id)->all();

        if ($industryIds === []) {
            return new FeedPage($this->trending->articles($locales, $perPage)->all(), null, 'trending');
        }

        $ranked = $this->rank($this->candidates($industryIds, $locales, $cursor->asOf), $industryIds, $cursor->asOf);
        $ordered = $this->diversify($ranked);

        $page = array_slice($ordered, $cursor->offset, $perPage);
        $hasMore = $cursor->offset + $perPage < count($ordered);

        return new FeedPage($page, $hasMore ? $cursor->next($perPage)->encode() : null);
    }

    /**
     * Articles of one industry, newest first, with up to three upcoming events woven into the first page.
     *
     * @param  list<string>  $locales
     */
    public function forIndustry(Industry $industry, array $locales, ?string $cursor, int $perPage = 20): FeedPage
    {
        $articles = ContentQueries::inIndustries(ContentQueries::articles($locales), [$industry->slug])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage, cursor: $cursor);

        $items = $articles->items();

        if ($cursor === null) {
            $events = ContentQueries::inIndustries(ContentQueries::events($locales), [$industry->slug])
                ->upcoming()
                ->orderBy('starts_at')
                ->limit(count(self::INDUSTRY_FEED_EVENT_SLOTS))
                ->get();

            foreach ($events->values() as $index => $event) {
                array_splice($items, min(self::INDUSTRY_FEED_EVENT_SLOTS[$index], count($items)), 0, [$event]);
            }
        }

        return new FeedPage(array_values($items), $articles->nextCursor()?->encode());
    }

    /**
     * @param  list<int>  $industryIds
     * @param  list<string>  $locales
     * @return Collection<int, Article|Event>
     */
    private function candidates(array $industryIds, array $locales, CarbonImmutable $asOf): Collection
    {
        $inIndustries = fn (Builder $industries) => $industries->whereIn('industries.id', $industryIds);

        $articles = ContentQueries::articles($locales)
            ->whereHas('industries', $inIndustries)
            ->whereBetween('published_at', [$asOf->subDays(self::POOL_DAYS), $asOf])
            ->orderByDesc('published_at')
            ->limit(self::POOL_LIMIT)
            ->get();

        $events = ContentQueries::events($locales)
            ->whereHas('industries', $inIndustries)
            ->where(fn (Builder $published) => $published->whereNull('published_at')->orWhere('published_at', '<=', $asOf))
            ->whereBetween('starts_at', [$asOf, $asOf->addDays(self::EVENT_HORIZON_DAYS)])
            ->get();

        /** @var Collection<int, Article|Event> $candidates */
        $candidates = collect([...$articles->all(), ...$events->all()]);

        return $candidates;
    }

    /**
     * @param  Collection<int, Article|Event>  $items
     * @param  list<int>  $industryIds
     * @return list<Article|Event>
     */
    public function rank(Collection $items, array $industryIds, CarbonImmutable $asOf): array
    {
        return $items
            ->map(fn (Article|Event $item) => ['item' => $item, 'score' => $this->score($item, $industryIds, $asOf)])
            ->sort(function (array $left, array $right): int {
                return [$right['score'], $right['item']->getKey()] <=> [$left['score'], $left['item']->getKey()];
            })
            ->pluck('item')
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $industryIds
     */
    public function score(Article|Event $item, array $industryIds, CarbonImmutable $asOf): float
    {
        $matches = $item->industries->whereIn('id', $industryIds)->count();
        $industryBoost = 1 + self::EXTRA_INDUSTRY_BOOST * min(max($matches - 1, 0), self::MAX_EXTRA_INDUSTRIES);

        if ($item instanceof Event) {
            $hoursUntilStart = max(0.0, $asOf->diffInHours($item->starts_at, false));

            return self::EVENT_BASE_WEIGHT
                * 0.5 ** ($hoursUntilStart / self::EVENT_HALF_LIFE_HOURS)
                * (1 + ($item->is_featured ? self::FEATURED_BOOST : 0))
                * $industryBoost;
        }

        $ageHours = max(0.0, $item->published_at?->diffInHours($asOf, false) ?? 0.0);

        return 0.5 ** ($ageHours / self::ARTICLE_HALF_LIFE_HOURS)
            * (1 + ($item->is_featured ? self::FEATURED_BOOST : 0) + ($item->is_original ? self::ORIGINAL_BOOST : 0))
            * $industryBoost;
    }

    /**
     * Greedy interleave: keep score order, but never allow more than MAX_SAME_SECTION_RUN consecutive
     * items from the same section when another section is available.
     *
     * @param  list<Article|Event>  $ranked
     * @return list<Article|Event>
     */
    public function diversify(array $ranked): array
    {
        $result = [];
        $lastSection = null;
        $run = 0;

        while ($ranked !== []) {
            $pick = 0;

            if ($run >= self::MAX_SAME_SECTION_RUN) {
                foreach ($ranked as $index => $candidate) {
                    if ($this->sectionOf($candidate) !== $lastSection) {
                        $pick = $index;
                        break;
                    }
                }
            }

            [$item] = array_splice($ranked, $pick, 1);
            $section = $this->sectionOf($item);
            $run = $section === $lastSection ? $run + 1 : 1;
            $lastSection = $section;
            $result[] = $item;
        }

        return $result;
    }

    private function sectionOf(Article|Event $item): string
    {
        return $item instanceof Event ? self::EVENTS_SECTION : $item->section->slug;
    }
}
