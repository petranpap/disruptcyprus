<?php

namespace App\Http\Requests\Content;

class SectionArticlesRequest extends CursorRequest
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
        return [...parent::rules(), ...$this->industryRules()];
    }
}
