<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\SearchRequest;
use App\Http\Resources\ArticleCardResource;
use App\Http\Resources\EventCardResource;
use App\Http\Resources\IndustryResource;
use App\Models\User;
use App\Services\Content\BookmarkState;
use App\Services\Search\SearchService;
use App\Support\ContentLocales;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, SearchService $search): JsonResponse
    {
        $results = $search->search((string) $request->validated('q'), ContentLocales::accepted($request));

        $user = $request->user();
        app(BookmarkState::class)->prime($user instanceof User ? $user : null, [...$results['articles'], ...$results['events']]);

        return response()->json(['data' => [
            'articles' => ArticleCardResource::collection($results['articles'])->toArray($request),
            'events' => EventCardResource::collection($results['events'])->toArray($request),
            'industries' => IndustryResource::collection($results['industries'])->toArray($request),
        ]]);
    }
}
