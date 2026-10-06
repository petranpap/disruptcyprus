<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Concerns\SavesEditorialContent;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Tables\ArticlesTable;
use App\Filament\Support\IndustryFields;
use App\Models\Article;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    use SavesEditorialContent;

    protected static string $resource = ArticleResource::class;

    protected function translatableFields(): array
    {
        return ['title', 'excerpt', 'body', 'hero_caption'];
    }

    protected function getHeaderActions(): array
    {
        return [
            ArticlesTable::previewAction()->record($this->getRecord()),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Article $article */
        $article = $this->getRecord();

        return IndustryFields::fill($article, $data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareEditorialData($data, (int) $this->getRecord()->getKey());
    }

    protected function afterSave(): void
    {
        $this->syncIndustries();
    }
}
