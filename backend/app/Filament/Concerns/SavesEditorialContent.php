<?php

namespace App\Filament\Concerns;

use App\Filament\Support\IndustryFields;
use App\Filament\Support\SlugGenerator;
use App\Filament\Support\Translatable;
use App\Models\Article;
use App\Models\Event;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

/**
 * Shared Create/Edit page behaviour for articles and events: clean translations, generate a unique slug,
 * and persist the industries + primary industry pivot after the record is saved.
 *
 * @mixin CreateRecord|EditRecord
 */
trait SavesEditorialContent
{
    /** @var array<int, array{is_primary: bool}> */
    protected array $industryPivot = [];

    /**
     * @return list<string>
     */
    abstract protected function translatableFields(): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareEditorialData(array $data, ?int $ignoreId = null): array
    {
        $data = Translatable::clean($data, $this->translatableFields());
        [$data, $this->industryPivot] = IndustryFields::extract($data);

        /** @var class-string<Article|Event> $modelClass */
        $modelClass = static::getModel();
        $data['slug'] = SlugGenerator::unique($modelClass, (array) ($data['title'] ?? []), $data['slug'] ?? null, $ignoreId);

        return $data;
    }

    protected function syncIndustries(): void
    {
        /** @var Article|Event $record */
        $record = $this->getRecord();
        $record->industries()->sync($this->industryPivot);
    }
}
