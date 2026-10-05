<?php

namespace App\Services\Account;

use App\Models\Bookmark;
use App\Models\Industry;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the GDPR data export (JSON) for a user and stores it on the private disk.
 */
class UserDataExporter
{
    public static function directoryFor(User $user): string
    {
        return 'exports/'.$user->id;
    }

    /**
     * @return string File name inside the user's export directory.
     */
    public function export(User $user): string
    {
        $fileName = Str::random(40).'.json';

        Storage::disk('local')->put(
            self::directoryFor($user).'/'.$fileName,
            (string) json_encode($this->collect($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $fileName;
    }

    /**
     * @return array<string, mixed>
     */
    public function collect(User $user): array
    {
        $user->loadMissing(['industries', 'socialAccounts', 'bookmarks.bookmarkable', 'notificationPreference']);

        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'locale' => $user->locale,
                'content_locales' => $user->content_locales,
                'timezone' => $user->timezone,
                'consent_at' => $user->consent_at?->toIso8601String(),
                'consent_version' => $user->consent_version,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'linked_accounts' => $user->socialAccounts
                ->map(fn (SocialAccount $account) => ['provider' => $account->provider, 'linked_at' => $account->created_at?->toIso8601String()])
                ->values(),
            'followed_industries' => $user->industries
                ->map(fn (Industry $industry) => ['slug' => $industry->slug, 'notify' => (bool) $industry->getRelationValue('pivot')->notify])
                ->values(),
            'notification_preferences' => $user->notificationPreference?->only([
                'digest_news_daily', 'digest_news_monthly', 'digest_events_weekly', 'digest_events_monthly', 'event_reminders', 'delivery_time',
            ]),
            'bookmarks' => $user->bookmarks
                ->map(fn (Bookmark $bookmark) => [
                    'type' => $bookmark->bookmarkable_type,
                    'slug' => $bookmark->bookmarkable?->getAttribute('slug'),
                    'saved_at' => $bookmark->created_at->toIso8601String(),
                ])
                ->values(),
            'notifications' => $user->notifications()->latest()->get()
                ->map(fn (DatabaseNotification $notification) => [
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])
                ->values(),
        ];
    }
}
