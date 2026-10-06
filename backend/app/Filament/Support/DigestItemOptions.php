<?php

namespace App\Filament\Support;

use App\Enums\DigestKind;
use App\Models\Article;
use App\Models\Digest;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Options for the digest item picker: articles for news digests, events for events digests.
 */
final class DigestItemOptions
{
    public const RECENT_LIMIT = 50;

    public const SEARCH_LIMIT = 30;

    /**
     * @return array<int, string>
     */
    public static function recent(Digest $digest): array
    {
        $query = $digest->kind === DigestKind::News
            ? Article::query()->published()->latest('published_at')
            : Event::query()->published()
                ->where('starts_at', '>=', CarbonImmutable::parse($digest->period_start)->subWeek())
                ->orderBy('starts_at');

        return self::labels($query->limit(self::RECENT_LIMIT));
    }

    /**
     * @return array<int, string>
     */
    public static function search(Digest $digest, string $search): array
    {
        $query = $digest->kind === DigestKind::News ? Article::query()->published() : Event::query()->published();

        return self::labels($query->where('search_text', 'like', '%'.$search.'%')->limit(self::SEARCH_LIMIT));
    }

    public static function label(Digest $digest, mixed $id): ?string
    {
        $item = $digest->kind === DigestKind::News ? Article::query()->find($id) : Event::query()->find($id);

        return $item === null ? null : self::format($item);
    }

    /**
     * @param  Builder<Article>|Builder<Event>  $query
     * @return array<int, string>
     */
    private static function labels(Builder $query): array
    {
        return $query->get()->mapWithKeys(fn (Article|Event $item) => [$item->id => self::format($item)])->all();
    }

    private static function format(Article|Event $item): string
    {
        $date = $item instanceof Event ? $item->starts_at : $item->published_at;

        return $item->title.' · '.$date?->timezone(config('app.business_timezone'))->format('d M');
    }
}
