<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\SectionArticlesRequest;
use App\Http\Resources\ArticleCardResource;
use App\Models\Section;
use App\Models\User;
use App\Services\Content\BookmarkState;
use App\Services\Feed\ContentQueries;
use App\Support\ContentLocales;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SectionArticlesController extends Controller
{
    public const PER_PAGE = 20;

    public function __invoke(SectionArticlesRequest $request, string $slug): AnonymousResourceCollection
    {
        $section = Section::query()->where('slug', $slug)->where('has_articles', true)->firstOrFail();

        $articles = ContentQueries::inIndustries(ContentQueries::articles(ContentLocales::accepted($request)), $request->industrySlugs())
            ->where('section_id', $section->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate(self::PER_PAGE, cursor: $request->cursor());

        $user = $request->user();
        app(BookmarkState::class)->prime($user instanceof User ? $user : null, $articles->items());

        return ArticleCardResource::collection($articles);
    }
}
