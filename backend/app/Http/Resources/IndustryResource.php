<?php

namespace App\Http\Resources;

use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Industry
 */
class IndustryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageUrl = $this->getFirstMediaUrl(Industry::IMAGE_COLLECTION, 'tile');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'group' => $this->group->value,
            'color' => $this->color,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'sort_order' => $this->sort_order,
        ];
    }
}
