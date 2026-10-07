<?php

namespace App\Listeners;

use App\Events\DigestPublished;
use App\Models\User;
use App\Notifications\DigestPublishedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

/**
 * Notifies subscribers of this digest type at their preferred delivery time, in their own timezone.
 * Published after today's delivery time: sent now during the day, otherwise held until tomorrow's slot.
 */
class QueueDigestNotifications implements ShouldQueue
{
    /** Local hours in which a late digest may still be sent straight away. */
    private const int DAYTIME_START = 7;

    private const int DAYTIME_END = 22;

    public function handle(DigestPublished $event): void
    {
        $digest = $event->digest;
        $flag = "digest_{$digest->kind->value}_{$digest->cadence->value}";

        User::query()
            ->whereHas('notificationPreference', fn (Builder $preferences) => $preferences->where($flag, true))
            ->with('notificationPreference')
            ->chunkById(500, function ($users) use ($digest): void {
                foreach ($users as $user) {
                    if (! $user->claimDispatch("digest:{$digest->id}")) {
                        continue;
                    }

                    $user->notify((new DigestPublishedNotification($digest))->delay($this->deliveryAt($user)));
                }
            });
    }

    /**
     * When this reader should receive the digest; null means now.
     */
    public function deliveryAt(User $user, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        $local = ($now ?? CarbonImmutable::now())->setTimezone($user->timezone ?: config('app.business_timezone'));
        [$hour, $minute] = array_map('intval', explode(':', (string) ($user->notificationPreference->delivery_time ?? '08:00')));
        $slot = $local->setTime($hour, $minute);

        if ($slot->isAfter($local)) {
            return $slot->utc();
        }

        if ($local->hour >= self::DAYTIME_START && $local->hour < self::DAYTIME_END) {
            return null;
        }

        return $slot->addDay()->utc();
    }
}
