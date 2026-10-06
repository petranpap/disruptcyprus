<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\CursorRequest;
use App\Http\Resources\ContentCards;
use App\Http\Resources\IndustryResource;
use App\Models\Industry;
use App\Services\Feed\FeedService;
use App\Support\ContentLocales;
use Illuminate\Http\JsonResponse;

class IndustryFeedController extends Controller
{
    public const PER_PAGE = 20;

    public function __invoke(CursorRequest $request, string $slug, FeedService $feed): JsonResponse
    {
        $industry = Industry::query()->active()->where('slug', $slug)->firstOrFail();

        $page = $feed->forIndustry($industry, ContentLocales::accepted($request), $request->cursor(), self::PER_PAGE);

        return ContentCards::response($request, $page->items, $page->nextCursor, self::PER_PAGE, [
            'industry' => (new IndustryResource($industry))->toArray($request),
        ]);
    }
}
