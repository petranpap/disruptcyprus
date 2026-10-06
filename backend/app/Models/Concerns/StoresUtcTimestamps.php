<?php

namespace App\Models\Concerns;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eloquent writes a Carbon instance using its own wall-clock time, so a Nicosia time would be stored
 * three hours off. Normalizing to UTC (the app timezone) before formatting keeps stored instants correct.
 *
 * @mixin Model
 */
trait StoresUtcTimestamps
{
    /**
     * @param  mixed  $value
     */
    public function fromDateTime($value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            $value = Carbon::instance($value)->setTimezone((string) config('app.timezone'));
        }

        return parent::fromDateTime($value);
    }
}
