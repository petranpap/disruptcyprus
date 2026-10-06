<?php

namespace App\Http\Requests\Content;

use Illuminate\Validation\Rule;

class IndexBookmarksRequest extends CursorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'type' => ['sometimes', Rule::in(array_keys(BookmarkRequest::TYPES))],
        ];
    }
}
