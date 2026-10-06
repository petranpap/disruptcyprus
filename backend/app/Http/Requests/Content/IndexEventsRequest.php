<?php

namespace App\Http\Requests\Content;

use App\Services\Content\EventRange;
use Illuminate\Validation\Rule;

class IndexEventsRequest extends CursorRequest
{
    use FiltersByIndustry;

    protected function prepareForValidation(): void
    {
        $this->normalizeIndustryFilter();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->industryRules(),
            'range' => ['sometimes', Rule::enum(EventRange::class)],
            'city' => ['sometimes', 'string', 'max:96'],
            'online' => ['sometimes', 'boolean'],
        ];
    }

    public function range(): EventRange
    {
        return EventRange::from((string) $this->validated('range', EventRange::Upcoming->value));
    }
}
