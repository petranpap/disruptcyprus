<?php

namespace App\Filament\Resources\PushCampaigns\Pages;

use App\Filament\Resources\PushCampaigns\PushCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePushCampaign extends CreateRecord
{
    protected static string $resource = PushCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['industry_ids'] = ($data['audience'] ?? 'all') === 'industries' ? array_map('intval', $data['industry_ids'] ?? []) : null;

        return $data;
    }
}
