<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Compact event used in lists, feeds, calendar days, search and bookmarks.
 *
 * @property Event $resource
 */
class EventCardResource extends LocalizedContentResource
{
    public const EXCERPT_LENGTH = 160;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource;
        $description = $this->translated($request, 'description');

        return [
            'type' => 'event',
            'id' => $event->id,
            'slug' => $event->slug,
            'title' => $this->translated($request, 'title'),
            'excerpt' => $description === null ? null : Str::limit(trim(strip_tags($description)), self::EXCERPT_LENGTH),
            'starts_at' => $event->starts_at->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'timezone' => $event->timezone,
            'is_online' => $event->is_online,
            'city' => $event->city,
            'location_name' => $event->location_name,
            'price_info' => $this->translated($request, 'price_info'),
            'primary_industry' => $this->industryBadge($event->primaryIndustry()),
            'industries' => $this->industryList(),
            'is_featured' => $event->is_featured,
            'image' => $this->heroImage($event, Event::HERO_COLLECTION),
            ...$this->localeMeta($request),
            'is_bookmarked' => $this->isBookmarked($request),
        ];
    }
}
