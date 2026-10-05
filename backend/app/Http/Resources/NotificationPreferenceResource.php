<?php

namespace App\Http\Resources;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NotificationPreference
 */
class NotificationPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'digest_news_daily' => $this->digest_news_daily,
            'digest_news_monthly' => $this->digest_news_monthly,
            'digest_events_weekly' => $this->digest_events_weekly,
            'digest_events_monthly' => $this->digest_events_monthly,
            'event_reminders' => $this->event_reminders,
            'delivery_time' => substr((string) $this->delivery_time, 0, 5),
        ];
    }
}
