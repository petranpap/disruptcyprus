<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Base for reader notifications: always the in-app inbox, plus Web Push when the reader has a subscribed device.
 * One payload ({type, title, body, url}) feeds both channels; Laravel renders it in the reader's locale
 * (User::preferredLocale), so new channels (e.g. email digests) can reuse it.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Push notifications older than this are no longer worth showing. */
    protected int $pushTtlSeconds = 24 * 60 * 60;

    /**
     * @return array{type: string, title: string, body: string, url: string, tag?: string}
     */
    abstract public function payload(User $notifiable): array;

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->pushSubscriptions()->exists() ? ['database', WebPushChannel::class] : ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(User $notifiable): array
    {
        $payload = $this->payload($notifiable);
        unset($payload['tag']);

        return $payload;
    }

    public function toWebPush(User $notifiable, Notification $notification): WebPushMessage
    {
        $payload = $this->payload($notifiable);

        return (new WebPushMessage)
            ->title($payload['title'])
            ->body($payload['body'])
            ->icon('/pwa-192x192.png')
            ->badge('/pwa-64x64.png')
            ->lang($notifiable->locale)
            ->tag($payload['tag'] ?? $payload['type'])
            ->data(['url' => $payload['url'], 'type' => $payload['type'], ...$this->extraPushData()])
            ->options(['TTL' => $this->pushTtlSeconds, 'urgency' => 'normal']);
    }

    /**
     * @return array<string, int|string>
     */
    protected function extraPushData(): array
    {
        return [];
    }
}
