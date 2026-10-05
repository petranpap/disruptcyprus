<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class DataExportReady extends Notification
{
    use Queueable;

    public const LINK_VALID_HOURS = 48;

    public function __construct(public string $fileName) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function downloadUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'me.export.download',
            now()->addHours(self::LINK_VALID_HOURS),
            ['user' => $notifiable->id, 'file' => $this->fileName],
        );
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.data_export.title'))
            ->line(__('notifications.data_export.body', ['hours' => self::LINK_VALID_HOURS]))
            ->action(__('notifications.data_export.action'), $this->downloadUrl($notifiable));
    }

    /**
     * Shape shared by every in-app notification: {type, title, body, url}.
     *
     * @return array<string, string>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'data_export_ready',
            'title' => __('notifications.data_export.title'),
            'body' => __('notifications.data_export.body', ['hours' => self::LINK_VALID_HOURS]),
            'url' => $this->downloadUrl($notifiable),
        ];
    }
}
