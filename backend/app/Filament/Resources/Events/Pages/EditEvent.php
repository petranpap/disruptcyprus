<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Concerns\SavesEditorialContent;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\IndustryFields;
use App\Models\Event;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    use SavesEditorialContent;

    protected static string $resource = EventResource::class;

    protected function translatableFields(): array
    {
        return ['title', 'description', 'price_info'];
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Event $event */
        $event = $this->getRecord();

        return IndustryFields::fill($event, $data);
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
