<?php

namespace App\Http\Resources;

use App\Models\Article;
use App\Models\Digest;
use App\Models\DigestItem;
use Illuminate\Http\Request;

/**
 * Digest with its items. `highlighted_item_ids` holds items from industries the reader follows.
 *
 * @mixin Digest
 */
class DigestResource extends DigestCardResource
{
    /**
     * @param  list<int>  $highlightedItemIds
     */
    public function __construct(Digest $digest, private readonly array $highlightedItemIds = [])
    {
        parent::__construct($digest);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'items' => $this->items->map(fn (DigestItem $item) => [
                'id' => $item->id,
                'position' => $item->position,
                'editor_note' => $item->editor_note,
                'is_highlighted' => in_array($item->id, $this->highlightedItemIds, true),
                'item' => $item->itemable instanceof Article
                    ? (new ArticleCardResource($item->itemable))->toArray($request)
                    : (new EventCardResource($item->itemable))->toArray($request),
            ])->values()->all(),
        ];
    }
}
