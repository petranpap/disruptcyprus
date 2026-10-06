<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\CursorRequest;
use App\Http\Resources\ContentCards;
use App\Models\User;
use App\Services\Feed\FeedCursor;
use App\Services\Feed\FeedService;
use App\Services\Feed\TrendingService;
use App\Support\ContentLocales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public const PER_PAGE = 20;

    public const TRENDING_LIMIT = 10;

    public function forYou(CursorRequest $request, FeedService $feed): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $page = $feed->forYou($user, ContentLocales::accepted($request), FeedCursor::decode($request->cursor()), self::PER_PAGE);

        return ContentCards::response($request, $page->items, $page->nextCursor, self::PER_PAGE, ['fallback' => $page->fallback]);
    }

    /**
     * Ranked list for the "Trending today" carousel; order = rank.
     */
    public function trending(Request $request, TrendingService $trending): JsonResponse
    {
        $articles = $trending->articles(ContentLocales::accepted($request), self::TRENDING_LIMIT)->all();

        return ContentCards::response($request, $articles, null, self::TRENDING_LIMIT);
    }
}
