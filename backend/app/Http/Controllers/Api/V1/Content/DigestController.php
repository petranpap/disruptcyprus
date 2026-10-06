<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\IndexDigestsRequest;
use App\Http\Requests\Content\LatestDigestRequest;
use App\Http\Resources\DigestCardResource;
use App\Http\Resources\DigestResource;
use App\Models\Article;
use App\Models\Digest;
use App\Models\DigestItem;
use App\Models\Event;
use App\Models\User;
use App\Services\Content\BookmarkState;
use App\Services\Feed\ContentQueries;
use App\Support\ContentLocales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DigestController extends Controller
{
    public const PER_PAGE = 20;

    public function index(IndexDigestsRequest $request): AnonymousResourceCollection
    {
        $digests = Digest::query()->published()
            ->when($request->kind(), fn (Builder $query, $kind) => $query->where('kind', $kind))
            ->when($request->cadence(), fn (Builder $query, $cadence) => $query->where('cadence', $cadence))
            ->withCount('items')
            ->with(['items' => fn ($items) => $items->limit(1)->with('itemable.media')])
            ->orderByDesc('period_start')
            ->orderByDesc('id')
            ->cursorPaginate(self::PER_PAGE, cursor: $request->cursor());

        return DigestCardResource::collection($digests);
    }

    public function latest(LatestDigestRequest $request): DigestResource
    {
        $digest = Digest::query()->published()
            ->where('kind', $request->kind())
            ->where('cadence', $request->cadence())
            ->orderByDesc('period_start')
            ->firstOrFail();

        return $this->present($request, $digest);
    }

    public function show(Request $request, string $slug): DigestResource
    {
        return $this->present($request, Digest::query()->published()->where('slug', $slug)->firstOrFail());
    }

    /**
     * Drops items that are unpublished or unreadable for this reader and flags items in followed industries.
     */
    private function present(Request $request, Digest $digest): DigestResource
    {
        $digest->load(['items.itemable' => fn (MorphTo $morph) => $morph->morphWith([
            Article::class => ContentQueries::ARTICLE_CARD_RELATIONS,
            Event::class => ContentQueries::EVENT_CARD_RELATIONS,
        ])]);

        $order = ContentLocales::preferenceOrder($request);

        $visible = $digest->items->filter(function (DigestItem $item) use ($order): bool {
            $content = $item->itemable;

            return ($content instanceof Article || $content instanceof Event)
                && $content->isPublished()
                && $content->resolveLocale($order[0], $order) !== null;
        })->values();

        $digest->setRelation('items', $visible);
        $digest->setAttribute('items_count', $visible->count());

        $user = $request->user();
        $user = $user instanceof User ? $user : null;
        app(BookmarkState::class)->prime($user, $visible->pluck('itemable'));

        $followed = $user?->industries()->pluck('industries.id')->all() ?? [];
        $highlighted = $visible
            ->filter(function (DigestItem $item) use ($followed): bool {
                $content = $item->itemable;

                return ($content instanceof Article || $content instanceof Event)
                    && $content->industries->whereIn('id', $followed)->isNotEmpty();
            })
            ->pluck('id')
            ->all();

        return new DigestResource($digest, array_values($highlighted));
    }
}
