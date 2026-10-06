<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Accepts `industry[]=a&industry[]=b` or `industry=a,b` (slugs).
 *
 * @mixin FormRequest
 */
trait FiltersByIndustry
{
    protected function normalizeIndustryFilter(): void
    {
        $industry = $this->input('industry');

        if (is_string($industry)) {
            $this->merge(['industry' => array_values(array_filter(array_map('trim', explode(',', $industry))))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function industryRules(): array
    {
        return [
            'industry' => ['sometimes', 'array', 'max:33'],
            'industry.*' => ['string', 'distinct', 'exists:industries,slug'],
        ];
    }

    /**
     * @return list<string>
     */
    public function industrySlugs(): array
    {
        return array_values(array_map('strval', $this->validated('industry', [])));
    }
}
