<?php

namespace App\Jobs;

use App\Enums\PushCampaignStatus;
use App\Models\PushCampaign;
use App\Notifications\CampaignNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Delivers a manual campaign to its audience: in-app for everyone, Web Push for readers with a subscribed device.
 * recipients_count counts readers; failures_count is incremented per failed push (CountCampaignPushFailures).
 */
class SendPushCampaign implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public PushCampaign $campaign) {}

    public function handle(): void
    {
        $recipients = 0;

        $this->campaign->audienceQuery()->chunkById(500, function ($users) use (&$recipients): void {
            foreach ($users as $user) {
                $user->notify(new CampaignNotification($this->campaign));
                $recipients++;
            }
        });

        $this->campaign->forceFill([
            'status' => PushCampaignStatus::Sent,
            'recipients_count' => $recipients,
            'sent_at' => now(),
        ])->save();
    }

    public function failed(): void
    {
        $this->campaign->forceFill(['status' => PushCampaignStatus::Failed])->save();
    }
}
