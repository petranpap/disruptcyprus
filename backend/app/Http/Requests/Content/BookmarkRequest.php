<?php

namespace App\Http\Requests\Content;

use App\Models\Article;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookmarkRequest extends FormRequest
{
    public const TYPES = ['article' => Article::class, 'event' => Event::class];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * The published article or event being (un)saved.
     */
    public function target(): Article|Event
    {
        $modelClass = self::TYPES[(string) $this->validated('type')];

        return $modelClass::query()->published()->findOrFail((int) $this->validated('id'));
    }
}
