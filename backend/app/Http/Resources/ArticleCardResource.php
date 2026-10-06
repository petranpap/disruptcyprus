<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;

/**
 * Compact article used in feeds, carousels, search and bookmarks.
 *
 * @property Article $resource
 */
class ArticleCardResource extends LocalizedContentResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $article = $this->resource;
        $locale = $this->contentLocale($request);

        return [
            'type' => 'article',
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $this->translated($request, 'title'),
            'excerpt' => $this->translated($request, 'excerpt'),
            'section' => ['slug' => $article->section->slug, 'name' => $article->section->name],
            'primary_industry' => $this->industryBadge($article->primaryIndustry()),
            'industries' => $this->industryList(),
            'author' => [
                'id' => $article->author->id,
                'name' => $article->author->name,
                'is_verified' => $article->author->is_verified,
            ],
            'is_original' => $article->is_original,
            'is_featured' => $article->is_featured,
            'published_at' => $article->published_at?->toIso8601String(),
            'reading_time_minutes' => $article->reading_time_minutes[$locale] ?? null,
            'reads' => $article->view_count,
            'image' => $this->heroImage($article, Article::HERO_COLLECTION),
            ...$this->localeMeta($request),
            'is_bookmarked' => $this->isBookmarked($request),
        ];
    }
}
