<?php

namespace App\Services\Content;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Counts a read at most once per viewer and item every 30 minutes, into the lifetime counter
 * and an hourly bucket used by trending. Viewers are anonymous fingerprints (no consent needed).
 */
class ViewRecorder
{
    public const DEDUPE_MINUTES = 30;

    public function record(Model $viewable, string $fingerprint): bool
    {
        $key = sprintf('viewed:%s:%s:%s', $viewable->getMorphClass(), $viewable->getKey(), $fingerprint);

        if (! Cache::add($key, true, now()->addMinutes(self::DEDUPE_MINUTES))) {
            return false;
        }

        $viewable->newQuery()->whereKey($viewable->getKey())->increment('view_count');

        DB::table('content_view_stats')->upsert(
            [[
                'viewable_type' => $viewable->getMorphClass(),
                'viewable_id' => $viewable->getKey(),
                'bucket_at' => CarbonImmutable::now()->startOfHour(),
                'views' => 1,
            ]],
            ['viewable_type', 'viewable_id', 'bucket_at'],
            ['views' => DB::raw('views + 1')],
        );

        return true;
    }
}
