<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;

class EventReminderNotification extends AppNotification
{
    /** A reminder is pointless after the event starts. */
    protected int $pushTtlSeconds = 23 * 60 * 60;

    public function __construct(public Event $event) {}

    public function shouldSend(User $notifiable, string $channel): bool
    {
        return Event::query()->published()->whereKey($this->event->id)->where('starts_at', '>', now())->exists();
    }

    public function payload(User $notifiable): array
    {
        $locale = $this->event->resolveLocale($notifiable->locale, $notifiable->content_locales) ?? $notifiable->locale;
        $time = $this->event->starts_at->copy()->setTimezone($this->event->timezone)->locale($notifiable->locale)
            ->isoFormat($notifiable->locale === 'el' ? 'dddd HH:mm' : 'dddd h:mm A');
        $place = $this->event->is_online ? __('notifications.event_reminder.online') : (string) $this->event->city;

        return [
            'type' => 'event_reminder',
            'title' => __('notifications.event_reminder.title', ['title' => (string) $this->event->getTranslation('title', $locale)]),
            'body' => __('notifications.event_reminder.body', ['time' => $time, 'place' => $place]),
            'url' => '/events/'.$this->event->slug,
            'tag' => 'event-'.$this->event->id,
        ];
    }
}
