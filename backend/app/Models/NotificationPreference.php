<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['digest_news_daily', 'digest_news_monthly', 'digest_events_weekly', 'digest_events_monthly', 'event_reminders', 'delivery_time'])]
class NotificationPreference extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'digest_news_daily' => false,
        'digest_news_monthly' => false,
        'digest_events_weekly' => false,
        'digest_events_monthly' => false,
        'event_reminders' => true,
        'delivery_time' => '08:00:00',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'digest_news_daily' => 'boolean',
            'digest_news_monthly' => 'boolean',
            'digest_events_weekly' => 'boolean',
            'digest_events_monthly' => 'boolean',
            'event_reminders' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
