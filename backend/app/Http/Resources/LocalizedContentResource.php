<?php

namespace App\Http\Resources;

use App\Models\Article;
use App\Models\Event;
use App\Models\Industry;
use App\Models\User;
use App\Services\Content\BookmarkState;
use App\Support\ContentLocales;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\HasMedia;

/**
 * Base for article/event resources: renders each item in the best acceptable language
 * (UI language first, then the reader's other content languages) and reports fallbacks.
 *
 * @property Article|Event $resource
 */
abstract class LocalizedContentResource extends JsonResource
{
    private ?string $contentLocale = null;

    protected function contentLocale(Request $request): string
    {
        return $this->contentLocale ??= $this->resource->resolveLocale(
            ContentLocales::preferenceOrder($request)[0],
            ContentLocales::preferenceOrder($request),
        ) ?? ($this->resource->available_locales[0] ?? app()->getLocale());
    }

    protected function translated(Request $request, string $field): ?string
    {
        $value = $this->resource->getTranslation($field, $this->contentLocale($request), false);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @return array{locale: string, is_fallback: bool}
     */
    protected function localeMeta(Request $request): array
    {
        $locale = $this->contentLocale($request);

        return ['locale' => $locale, 'is_fallback' => $locale !== app()->getLocale()];
    }

    protected function isBookmarked(Request $request): bool
    {
        $user = $request->user();

        return app(BookmarkState::class)->has($user instanceof User ? $user : null, $this->resource);
    }

    /**
     * @return array{slug: string, name: string, color: string}|null
     */
    protected function industryBadge(?Industry $industry): ?array
    {
        return $industry === null ? null : [
            'slug' => $industry->slug,
            'name' => $industry->name,
            'color' => $industry->color,
        ];
    }

    /**
     * @return list<array{slug: string, name: string, color: string, is_primary: bool}>
     */
    protected function industryList(): array
    {
        return $this->resource->industries
            ->map(fn (Industry $industry) => [
                'slug' => $industry->slug,
                'name' => $industry->name,
                'color' => $industry->color,
                'is_primary' => (bool) $industry->getRelationValue('pivot')->is_primary,
            ])
            ->values()
            ->all();
    }

    /**
     * Renditions of the hero image; a rendition not generated yet falls back to the original.
     *
     * @return array{thumb: string, card: string, hero: string}|null
     */
    protected function heroImage(HasMedia $model, string $collection): ?array
    {
        $media = $model->getFirstMedia($collection);

        if ($media === null) {
            return null;
        }

        return [
            'thumb' => $media->getAvailableUrl(['thumb']),
            'card' => $media->getAvailableUrl(['card']),
            'hero' => $media->getAvailableUrl(['hero']),
        ];
    }
}
