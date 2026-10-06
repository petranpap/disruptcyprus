<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Keeps the `search_text` column (FULLTEXT-indexed) in sync: plain text of every locale of the
 * translatable fields plus any plain columns, so one query searches Greek and English at once.
 *
 * @mixin Model
 */
trait MaintainsSearchText
{
    /**
     * @return list<string>
     */
    abstract protected function searchableTranslatableFields(): array;

    /**
     * @return list<string|null>
     */
    protected function searchablePlainValues(): array
    {
        return [];
    }

    public static function bootMaintainsSearchText(): void
    {
        static::saving(function (self $model): void {
            $model->setAttribute('search_text', $model->buildSearchText());
        });
    }

    public function buildSearchText(): string
    {
        $parts = [];

        foreach ($this->searchableTranslatableFields() as $field) {
            foreach ($this->getTranslations($field) as $value) {
                $parts[] = $value;
            }
        }

        $parts = [...$parts, ...$this->searchablePlainValues()];

        $text = html_entity_decode(strip_tags(implode(' ', array_map(fn ($part) => str_replace('>', '> ', (string) $part), $parts))), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
