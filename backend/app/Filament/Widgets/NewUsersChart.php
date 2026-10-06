<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class NewUsersChart extends ChartWidget
{
    public const DAYS = 30;

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return __('admin.widgets.new_users_per_day');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $timezone = (string) config('app.business_timezone');
        $start = CarbonImmutable::now($timezone)->startOfDay()->subDays(self::DAYS - 1);

        $counts = User::query()
            ->where('created_at', '>=', $start->utc())
            ->pluck('created_at')
            ->countBy(fn ($createdAt) => CarbonImmutable::instance($createdAt)->setTimezone($timezone)->toDateString());

        $labels = $data = [];
        for ($day = 0; $day < self::DAYS; $day++) {
            $date = $start->addDays($day);
            $labels[] = $date->format('d/m');
            $data[] = $counts->get($date->toDateString(), 0);
        }

        return [
            'datasets' => [['label' => __('admin.widgets.new_users'), 'data' => $data, 'borderColor' => '#00A5E6', 'fill' => false]],
            'labels' => $labels,
        ];
    }
}
