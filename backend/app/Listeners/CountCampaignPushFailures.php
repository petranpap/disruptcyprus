<?php

namespace App\Listeners;

use App\Models\PushCampaign;
use NotificationChannels\WebPush\Events\NotificationFailed;

/**
 * Failed deliveries of campaign pushes are counted on the campaign (expired endpoints are removed by the channel).
 */
class CountCampaignPushFailures
{
    public function handle(NotificationFailed $event): void
    {
        $campaignId = $event->message->toArray()['data']['campaign_id'] ?? null;

        if ($campaignId !== null) {
            PushCampaign::query()->whereKey($campaignId)->increment('failures_count');
        }
    }
}
