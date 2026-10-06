<?php

namespace App\Services\Content;

use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;

/**
 * Date windows for the Events tabs, in the business timezone, as UTC instants [from, to).
 */
enum EventRange: string
{
    case Upcoming = 'upcoming';
    case Week = 'week';
    case Month = 'month';

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null Null for "upcoming" (open-ended).
     */
    public function window(?CarbonImmutable $now = null): ?array
    {
        $local = ($now ?? CarbonImmutable::now())->setTimezone(DigestPeriod::timezone());

        return match ($this) {
            self::Upcoming => null,
            self::Week => [$local->startOfWeek()->utc(), $local->endOfWeek()->addSecond()->startOfDay()->utc()],
            self::Month => [$local->startOfMonth()->utc(), $local->endOfMonth()->addSecond()->startOfDay()->utc()],
        };
    }
}
