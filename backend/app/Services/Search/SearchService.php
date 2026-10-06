<?php

namespace App\Services\Search;

use App\Models\Article;
use App\Models\Event;
use App\Models\Industry;
use App\Services\Feed\ContentQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Normalizer;

/**
 * Search over the FULLTEXT `search_text` columns. Words of 3+ characters use boolean prefix matching
 * (accent- and case-insensitive via utf8mb4_unicode_ci); shorter words such as "AI" are below
 * MariaDB's minimum token size, so they are matched as whole words with a regular expression.
 */
class SearchService
{
    public const MIN_FULLTEXT_LENGTH = 3;

    public const MAX_TERMS = 8;

    /**
     * @param  list<string>  $locales
     * @return array{articles: Collection<int, Article>, events: Collection<int, Event>, industries: Collection<int, Industry>}
     */
    public function search(string $query, array $locales, int $articleLimit = 10, int $eventLimit = 5): array
    {
        $terms = $this->terms($query);

        if ($terms === []) {
            return ['articles' => collect(), 'events' => collect(), 'industries' => collect()];
        }

        $articles = $this->matching(ContentQueries::articles($locales), $terms)
            ->orderByDesc('published_at')
            ->limit($articleLimit)
            ->get();

        $events = $this->matching(ContentQueries::events($locales), $terms)
            ->orderByRaw('CASE WHEN starts_at >= ? THEN 0 ELSE 1 END', [now()])
            ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, ?, starts_at))', [now()])
            ->limit($eventLimit)
            ->get();

        return ['articles' => $articles, 'events' => $events, 'industries' => $this->industries($terms)];
    }

    /**
     * @return list<string>
     */
    public function terms(string $query): array
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($query)), flags: PREG_SPLIT_NO_EMPTY) ?: [];
        $clean = array_map(fn (string $word) => (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $word), $words);

        return array_slice(array_values(array_unique(array_filter($clean, fn (string $word) => $word !== ''))), 0, self::MAX_TERMS);
    }

    /**
     * @template TModel of Article|Event
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $terms
     * @return Builder<TModel>
     */
    private function matching(Builder $query, array $terms): Builder
    {
        $long = array_filter($terms, fn (string $term) => mb_strlen($term) >= self::MIN_FULLTEXT_LENGTH);
        $short = array_filter($terms, fn (string $term) => mb_strlen($term) < self::MIN_FULLTEXT_LENGTH);

        if ($long !== []) {
            $expression = implode(' ', array_map(fn (string $term) => '+'.$term.'*', $long));
            $query->whereRaw('MATCH(search_text) AGAINST (? IN BOOLEAN MODE)', [$expression]);
        }

        foreach ($short as $term) {
            $query->whereRaw('search_text REGEXP ?', ['(^|[^[:alnum:]])'.preg_quote($term).'([^[:alnum:]]|$)']);
        }

        return $query;
    }

    /**
     * Industries whose names (any locale), slug words or acronym ("AI" → Artificial Intelligence)
     * start with every search term.
     *
     * @param  list<string>  $terms
     * @return Collection<int, Industry>
     */
    private function industries(array $terms): Collection
    {
        $needles = array_map(fn (string $term) => $this->fold($term), $terms);

        return Industry::query()->active()->ordered()->get()
            ->filter(function (Industry $industry) use ($needles): bool {
                $words = $this->industryWords($industry);

                foreach ($needles as $needle) {
                    if (! collect($words)->contains(fn (string $word) => str_starts_with($word, $needle))) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    /**
     * @return list<string>
     */
    private function industryWords(Industry $industry): array
    {
        $words = [];

        foreach ([$industry->slug, ...array_values($industry->getTranslations('name'))] as $label) {
            $parts = preg_split('/[^\p{L}\p{N}]+/u', $this->fold((string) $label), flags: PREG_SPLIT_NO_EMPTY) ?: [];
            $words = [...$words, ...$parts];

            if (count($parts) > 1) {
                $words[] = implode('', array_map(fn (string $part) => mb_substr($part, 0, 1), $parts));
            }
        }

        return array_values(array_unique($words));
    }

    /**
     * Lowercase and strip accents (Greek tonos included) for in-memory comparisons.
     */
    private function fold(string $text): string
    {
        $decomposed = Normalizer::normalize(mb_strtolower($text), Normalizer::FORM_D) ?: $text;

        return (string) preg_replace('/\p{Mn}+/u', '', $decomposed);
    }
}
