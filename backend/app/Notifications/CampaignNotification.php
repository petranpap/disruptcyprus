<?php

namespace App\Notifications;

use App\Models\PushCampaign;
use App\Models\User;

/**
 * Manual campaign from the admin panel, written in both languages by editors.
 */
class CampaignNotification extends AppNotification
{
    public function __construct(public PushCampaign $campaign) {}

    public function payload(User $notifiable): array
    {
        $locale = $notifiable->locale;

        return [
            'type' => 'campaign',
            'title' => (string) $this->campaign->getTranslation('title', $locale),
            'body' => (string) $this->campaign->getTranslation('body', $locale),
            'url' => $this->campaign->url ?: '/',
            'tag' => 'campaign-'.$this->campaign->id,
        ];
    }

    protected function extraPushData(): array
    {
        return ['campaign_id' => $this->campaign->id];
    }
}
