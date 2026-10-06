<?php

namespace App\Http\Resources;

use App\Models\Article;
use App\Models\Event;
use App\Models\User;
use App\Services\Content\BookmarkState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Renders mixed article/event lists (feeds) with the same pagination meta as cursor paginators.
 */
final class ContentCards
{
    public static function card(Article|Event $item): ArticleCardResource|EventCardResource
    {
        return $item instanceof Article ? new ArticleCardResource($item) : new EventCardResource($item);
    }

    /**
     * @param  list<Article|Event>  $items
     * @param  array<string, mixed>  $meta
     */
    public static function response(Request $request, array $items, ?string $nextCursor, int $perPage, array $meta = []): JsonResponse
    {
        $user = $request->user();
        app(BookmarkState::class)->prime($user instanceof User ? $user : null, $items);

        return response()->json([
            'data' => array_map(fn (Article|Event $item) => self::card($item)->toArray($request), $items),
            'meta' => ['per_page' => $perPage, 'next_cursor' => $nextCursor, 'prev_cursor' => null, ...$meta],
        ]);
    }
}
