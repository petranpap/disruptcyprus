<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCardResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\User;
use App\Services\Content\BookmarkState;
use App\Services\Content\ViewRecorder;
use App\Services\Feed\ContentQueries;
use App\Support\ContentLocales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ArticleController extends Controller
{
    public const RELATED_LIMIT = 6;

    /**
     * Opening an article shows it even outside the reader's content languages (falls back to any available).
     */
    public function show(string $slug): ArticleResource
    {
        $article = Article::query()->published()->where('slug', $slug)
            ->with([...ContentQueries::ARTICLE_CARD_RELATIONS, 'author.media'])
            ->firstOrFail();

        return new ArticleResource($article);
    }

    public function related(Request $request, string $slug): AnonymousResourceCollection
    {
        $article = Article::query()->published()->where('slug', $slug)->with('industries')->firstOrFail();
        $industryIds = $article->industries->modelKeys();
        $sharesIndustry = fn (Builder $industries) => $industries->whereIn('industries.id', $industryIds);

        $related = ContentQueries::articles(ContentLocales::accepted($request))
            ->whereKeyNot($article->id)
            ->whereHas('industries', $sharesIndustry)
            ->withCount(['industries as shared_industries_count' => $sharesIndustry])
            ->orderByDesc('shared_industries_count')
            ->orderByDesc('published_at')
            ->limit(self::RELATED_LIMIT)
            ->get();

        $user = $request->user();
        app(BookmarkState::class)->prime($user instanceof User ? $user : null, $related);

        return ArticleCardResource::collection($related);
    }

    public function view(Request $request, int $id, ViewRecorder $views): Response
    {
        $article = Article::query()->published()->findOrFail($id);

        $views->record($article, $this->fingerprint($request));

        return response()->noContent();
    }

    /**
     * Anonymous viewer identity: the session when there is one, otherwise IP + user agent per day.
     */
    private function fingerprint(Request $request): string
    {
        if ($request->hasSession() && $request->session()->isStarted()) {
            return 's:'.hash('sha256', $request->session()->getId());
        }

        return 'a:'.hash('sha256', $request->ip().'|'.$request->userAgent().'|'.now()->toDateString());
    }
}
