<?php

namespace App\Http\Resources;

use App\Models\Digest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\HasMedia;

/**
 * @mixin Digest
 */
class DigestCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'kind' => $this->kind->value,
            'cadence' => $this->cadence->value,
            'period_start' => $this->period_start->toDateString(),
            'period_end' => $this->period_end->toDateString(),
            'title' => $this->title,
            'intro' => $this->intro,
            'published_at' => $this->published_at?->toIso8601String(),
            'items_count' => $this->whenCounted('items'),
            'cover_url' => $this->whenLoaded('items', fn () => $this->coverUrl()),
            'share_url' => rtrim((string) config('app.url'), '/').'/d/'.$this->slug,
        ];
    }

    private function coverUrl(): ?string
    {
        $first = $this->items->first()?->itemable;

        return $first instanceof HasMedia ? $first->getFirstMedia('hero')?->getAvailableUrl(['card']) : null;
    }
}
