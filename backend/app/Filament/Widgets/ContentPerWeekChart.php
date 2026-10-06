<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class ContentPerWeekChart extends ChartWidget
{
    public const WEEKS = 8;

    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return __('admin.widgets.content_per_week');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $timezone = (string) config('app.business_timezone');
        $firstWeek = CarbonImmutable::now($timezone)->startOfWeek()->subWeeks(self::WEEKS - 1);
        $labels = $articles = $events = [];

        for ($week = 0; $week < self::WEEKS; $week++) {
            $from = $firstWeek->addWeeks($week);
            $to = $from->addWeek();
            $labels[] = $from->translatedFormat('d M');
            $articles[] = Article::query()->published()->where('published_at', '>=', $from->utc())->where('published_at', '<', $to->utc())->count();
            $events[] = Event::query()->published()->where('published_at', '>=', $from->utc())->where('published_at', '<', $to->utc())->count();
        }

        return [
            'datasets' => [
                ['label' => __('admin.widgets.articles'), 'data' => $articles, 'backgroundColor' => '#0077B6'],
                ['label' => __('admin.widgets.events'), 'data' => $events, 'backgroundColor' => '#E21E49'],
            ],
            'labels' => $labels,
        ];
    }
}
