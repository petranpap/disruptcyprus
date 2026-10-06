<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\BookmarkRequest;
use App\Http\Requests\Content\IndexBookmarksRequest;
use App\Http\Resources\ContentCards;
use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Event;
use App\Models\User;
use App\Services\Feed\ContentQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookmarkController extends Controller
{
    public const PER_PAGE = 20;

    /**
     * Saved items, newest first. Items unpublished since saving are hidden.
     */
    public function index(IndexBookmarksRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $bookmarks = $user->bookmarks()
            ->when($request->validated('type'), fn (Builder $query, string $type) => $query->where('bookmarkable_type', $type))
            ->with('bookmarkable')
            ->orderByDesc('id')
            ->cursorPaginate(self::PER_PAGE, cursor: $request->cursor());

        $page = (new EloquentCollection($bookmarks->items()))->loadMorph('bookmarkable', [
            Article::class => ContentQueries::ARTICLE_CARD_RELATIONS,
            Event::class => ContentQueries::EVENT_CARD_RELATIONS,
        ]);

        $visible = $page->filter(function (Bookmark $bookmark): bool {
            $item = $bookmark->bookmarkable;

            return ($item instanceof Article || $item instanceof Event)
                && $item->isPublished();
        })->values();

        /** @var list<Article|Event> $items */
        $items = $visible->map(fn (Bookmark $bookmark) => $bookmark->bookmarkable)->all();

        $response = ContentCards::response(
            $request,
            $items,
            $bookmarks->nextCursor()?->encode(),
            self::PER_PAGE,
        );

        $payload = $response->getData(true);
        foreach ($visible->values() as $index => $bookmark) {
            $payload['data'][$index]['bookmarked_at'] = $bookmark->created_at->toIso8601String();
        }

        return $response->setData($payload);
    }

    public function store(BookmarkRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $target = $request->target();

        $bookmark = $user->bookmarks()->firstOrCreate([
            'bookmarkable_type' => $target->getMorphClass(),
            'bookmarkable_id' => $target->getKey(),
        ]);

        return response()->json(['data' => [
            'type' => $target->getMorphClass(),
            'id' => $target->getKey(),
            'is_bookmarked' => true,
            'bookmarked_at' => $bookmark->created_at->toIso8601String(),
        ]], $bookmark->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(BookmarkRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->bookmarks()
            ->where('bookmarkable_type', $request->validated('type'))
            ->where('bookmarkable_id', (int) $request->validated('id'))
            ->delete();

        return response()->noContent();
    }
}
