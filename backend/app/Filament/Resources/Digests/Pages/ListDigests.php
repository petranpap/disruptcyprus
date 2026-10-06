<?php

namespace App\Filament\Resources\Digests\Pages;

use App\Filament\Resources\Digests\DigestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDigests extends ListRecords
{
    protected static string $resource = DigestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
