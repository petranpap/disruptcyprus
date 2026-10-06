<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Concerns\SavesEditorialContent;
use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    use SavesEditorialContent;

    protected static string $resource = ArticleResource::class;

    protected function translatableFields(): array
    {
        return ['title', 'excerpt', 'body', 'hero_caption'];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareEditorialData($data);
    }

    protected function afterCreate(): void
    {
        $this->syncIndustries();
    }
}
