<?php

namespace App\Filament\Widgets;

use App\Enums\PushCampaignStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Event;
use App\Models\PushCampaign;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $weekStart = now(config('app.business_timezone'))->startOfWeek();
        $articlesThisWeek = Article::query()->published()->where('published_at', '>=', $weekStart)->count();
        $eventsThisWeek = Event::query()->published()->where('published_at', '>=', $weekStart)->count();
        $campaigns = PushCampaign::query()->where('status', PushCampaignStatus::Sent)->where('sent_at', '>=', now()->subDays(30));

        return [
            Stat::make(__('admin.widgets.readers'), User::query()->where('role', UserRole::Reader)->count())
                ->description(__('admin.widgets.readers_desc', ['count' => User::query()->where('created_at', '>=', now()->subDays(7))->count()])),
            Stat::make(__('admin.widgets.published_week'), $articlesThisWeek + $eventsThisWeek)
                ->description(__('admin.widgets.published_week_desc', ['articles' => $articlesThisWeek, 'events' => $eventsThisWeek])),
            Stat::make(__('admin.widgets.upcoming_events'), Event::query()->published()->whereBetween('starts_at', [now(), now()->addDays(30)])->count()),
            Stat::make(__('admin.widgets.push_sent'), (clone $campaigns)->count())
                ->description(__('admin.widgets.push_failures', ['count' => (int) (clone $campaigns)->sum('failures_count')]))
                ->color((int) (clone $campaigns)->sum('failures_count') > 0 ? 'danger' : 'gray'),
        ];
    }
}
