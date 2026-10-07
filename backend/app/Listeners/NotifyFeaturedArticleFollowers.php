<?php

namespace App\Listeners;

use App\Events\ContentPublished;
use App\Models\Article;
use App\Models\User;
use App\Notifications\FeaturedArticleNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * A featured article alerts readers who follow one of its industries with alerts on.
 * At most once per article and DAILY_LIMIT per reader in any 24 hours, so featuring stays meaningful.
 */
class NotifyFeaturedArticleFollowers implements ShouldQueue
{
    public const int DAILY_LIMIT = 3;

    public bool $afterCommit = true;

    /**
     * Grace period so the industries pivot is in place even when content is saved outside a transaction.
     */
    public function withDelay(ContentPublished $event): int
    {
        return 60;
    }

    public function handle(ContentPublished $event): void
    {
        $article = $event->content;

        if (! $article instanceof Article || ! $article->is_featured) {
            return;
        }

        $industryIds = $article->industries()->pluck('industries.id');

        if ($industryIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereHas('industries', fn (Builder $industries) => $industries
                ->whereIn('industries.id', $industryIds)
                ->where('industry_user.notify', true))
            ->chunkById(500, function ($users) use ($article): void {
                foreach ($users as $user) {
                    if ($this->reachedDailyLimit($user) || ! $user->claimDispatch("featured:{$article->id}")) {
                        continue;
                    }

                    $user->notify(new FeaturedArticleNotification($article));
                }
            });
    }

    private function reachedDailyLimit(User $user): bool
    {
        return DB::table('notification_dispatches')
            ->where('user_id', $user->id)
            ->where('key', 'like', 'featured:%')
            ->where('created_at', '>=', now()->subDay())
            ->count() >= self::DAILY_LIMIT;
    }
}
