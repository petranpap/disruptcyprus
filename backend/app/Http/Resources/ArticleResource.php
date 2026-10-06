<?php

namespace App\Http\Resources;

use App\Models\Article;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Full article for the reader.
 *
 * @property Article $resource
 */
class ArticleResource extends ArticleCardResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $article = $this->resource;
        $author = $article->author;
        $avatar = $author->getFirstMedia($author::AVATAR_COLLECTION);
        $attachment = $article->getFirstMedia(Article::ATTACHMENT_COLLECTION);

        return [
            ...parent::toArray($request),
            'body' => $this->sanitizedBody($request),
            'hero_caption' => $this->translated($request, 'hero_caption'),
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'title' => $author->title,
                'bio' => $author->bio,
                'avatar_url' => $avatar?->getAvailableUrl(['thumb']),
                'is_verified' => $author->is_verified,
            ],
            'attachment' => $attachment === null ? null : [
                'url' => $attachment->getUrl(),
                'file_name' => $attachment->file_name,
                'size' => $attachment->size,
                'mime_type' => $attachment->mime_type,
            ],
            'available_locales' => $article->available_locales,
            'share_url' => rtrim((string) config('app.url'), '/').'/a/'.$article->slug,
        ];
    }

    private function sanitizedBody(Request $request): string
    {
        $locale = $this->contentLocale($request);
        $article = $this->resource;
        $cacheKey = sprintf('article-body:%d:%d:%s', $article->id, $article->updated_at?->getTimestamp() ?? 0, $locale);

        return Cache::remember($cacheKey, now()->addDay(), fn () => app(HtmlSanitizer::class)->sanitize($this->translated($request, 'body')));
    }
}
