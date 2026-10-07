<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\User;

class FeaturedArticleNotification extends AppNotification
{
    public function __construct(public Article $article) {}

    public function shouldSend(User $notifiable, string $channel): bool
    {
        return Article::query()->published()->whereKey($this->article->id)->exists();
    }

    public function payload(User $notifiable): array
    {
        $locale = $this->article->resolveLocale($notifiable->locale, $notifiable->content_locales) ?? $notifiable->locale;
        $followed = $notifiable->industries()->pluck('industries.id')->all();
        $industry = $this->article->industries->firstWhere(fn ($candidate) => in_array($candidate->id, $followed, true)) ?? $this->article->primaryIndustry();

        return [
            'type' => 'featured_article',
            'title' => __('notifications.featured.title', ['industry' => $industry?->getTranslation('name', $notifiable->locale) ?? '']),
            'body' => (string) $this->article->getTranslation('title', $locale),
            'url' => '/articles/'.$this->article->slug,
            'tag' => 'article-'.$this->article->id,
        ];
    }
}
