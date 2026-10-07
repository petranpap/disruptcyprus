<?php

namespace App\Console\Commands;

use App\Models\Bookmark;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Hourly: reminds readers about saved events starting within the next day (once per event and reader).
 * The window starts an hour out so a reminder never arrives as the doors open.
 */
#[Signature('reminders:events')]
#[Description('Send reminders for saved events starting within 24 hours')]
class SendEventReminders extends Command
{
    public function handle(): int
    {
        $sent = 0;

        Event::query()
            ->published()
            ->whereBetween('starts_at', [now()->addHour(), now()->addDay()])
            ->each(function (Event $event) use (&$sent): void {
                $userIds = Bookmark::query()
                    ->where('bookmarkable_type', $event->getMorphClass())
                    ->where('bookmarkable_id', $event->id)
                    ->pluck('user_id');

                User::query()
                    ->whereKey($userIds)
                    ->with('notificationPreference')
                    ->each(function (User $user) use ($event, &$sent): void {
                        $wantsReminders = $user->notificationPreference->event_reminders ?? true;

                        if ($wantsReminders && $user->claimDispatch("event-reminder:{$event->id}")) {
                            $user->notify(new EventReminderNotification($event));
                            $sent++;
                        }
                    });
            });

        $this->info("Queued {$sent} event reminder(s).");

        return self::SUCCESS;
    }
}
