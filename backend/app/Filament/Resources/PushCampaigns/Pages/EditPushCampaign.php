<?php

namespace App\Filament\Resources\PushCampaigns\Pages;

use App\Filament\Resources\PushCampaigns\PushCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPushCampaign extends EditRecord
{
    protected static string $resource = PushCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [PushCampaignResource::sendAction()->record($this->getRecord()), DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['industry_ids'] = ($data['audience'] ?? 'all') === 'industries' ? array_map('intval', $data['industry_ids'] ?? []) : null;

        return $data;
    }
}
