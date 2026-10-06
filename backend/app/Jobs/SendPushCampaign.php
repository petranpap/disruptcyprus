<?php

namespace App\Jobs;

use App\Enums\PushCampaignStatus;
use App\Models\PushCampaign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Delivers a manual push campaign. Until Phase 6 adds Web Push, it records the audience size only.
 */
class SendPushCampaign implements ShouldQueue
{
    use Queueable;

    public function __construct(public PushCampaign $campaign) {}

    public function handle(): void
    {
        $recipients = $this->campaign->audienceQuery()->count();

        Log::info('Push campaign recorded; Web Push delivery arrives in Phase 6.', [
            'campaign' => $this->campaign->id,
            'recipients' => $recipients,
        ]);

        $this->campaign->forceFill([
            'status' => PushCampaignStatus::Sent,
            'recipients_count' => $recipients,
            'failures_count' => 0,
            'sent_at' => now(),
        ])->save();
    }

    public function failed(): void
    {
        $this->campaign->forceFill(['status' => PushCampaignStatus::Failed])->save();
    }
}
