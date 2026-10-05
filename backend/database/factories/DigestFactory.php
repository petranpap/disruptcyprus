<?php

namespace Database\Factories;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Enums\DigestStatus;
use App\Models\Digest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Digest>
 */
class DigestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $day = now()->subDays(fake()->unique()->numberBetween(1, 3000))->startOfDay();

        return [
            'kind' => DigestKind::News,
            'cadence' => DigestCadence::Daily,
            'period_start' => $day->toDateString(),
            'period_end' => $day->toDateString(),
            'slug' => 'daily-news-'.$day->toDateString().'-'.Str::lower(Str::random(4)),
            'title' => ['en' => 'Daily News', 'el' => 'Ημερήσια Ενημέρωση'],
            'intro' => null,
            'status' => DigestStatus::Published,
            'published_at' => now()->subHour(),
            'generated_automatically' => false,
        ];
    }
}
