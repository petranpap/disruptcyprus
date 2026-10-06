<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Concerns\SavesEditorialContent;
use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    use SavesEditorialContent;

    protected static string $resource = EventResource::class;

    protected function translatableFields(): array
    {
        return ['title', 'description', 'price_info'];
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
