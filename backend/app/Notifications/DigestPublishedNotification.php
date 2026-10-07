<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\Digest;
use App\Models\DigestItem;
use App\Models\Event;
use App\Models\User;

/**
 * "Daily News is ready": the body highlights the first item from an industry the reader follows.
 */
class DigestPublishedNotification extends AppNotification
{
    public function __construct(public Digest $digest) {}

    /** Delayed until the reader's delivery time: skip if editors unpublished it meanwhile. */
    public function shouldSend(User $notifiable, string $channel): bool
    {
        return Digest::query()->published()->whereKey($this->digest->id)->exists();
    }

    public function payload(User $notifiable): array
    {
        $locale = $notifiable->locale;
        $label = __("notifications.digest.labels.{$this->digest->kind->value}_{$this->digest->cadence->value}");
        $lead = $this->leadItemTitle($notifiable, $locale);

        return [
            'type' => 'digest_published',
            'title' => __('notifications.digest.title', ['label' => $label]),
            'body' => $lead !== null ? __('notifications.digest.body', ['title' => $lead]) : (string) $this->digest->getTranslation('title', $locale),
            'url' => '/digests/'.$this->digest->slug,
            'tag' => 'digest-'.$this->digest->id,
        ];
    }

    private function leadItemTitle(User $notifiable, string $locale): ?string
    {
        $this->digest->loadMissing('items.itemable.industries');
        $followed = $notifiable->industries()->pluck('industries.id');

        $contents = $this->digest->items
            ->map(fn (DigestItem $item) => $item->itemable)
            ->filter(fn ($content) => $content instanceof Article || $content instanceof Event);

        /** @var Article|Event|null $lead */
        $lead = $contents->first(fn (Article|Event $content) => $content->industries->pluck('id')->intersect($followed)->isNotEmpty())
            ?? $contents->first();

        return $lead !== null ? (string) $lead->getTranslation('title', $locale) : null;
    }
}
