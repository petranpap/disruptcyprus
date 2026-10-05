<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleEn = rtrim(fake('en_US')->sentence(5), '.');
        $startsAt = now()->addDays(fake()->numberBetween(1, 30))->setTime(18, 0);

        return [
            'slug' => Str::slug($titleEn).'-'.Str::lower(Str::random(5)),
            'title' => ['en' => $titleEn, 'el' => rtrim(fake('el_GR')->sentence(5), '.')],
            'description' => ['en' => fake('en_US')->paragraph(), 'el' => fake('el_GR')->paragraph()],
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
            'timezone' => 'Asia/Nicosia',
            'location_name' => 'Innovation Hub',
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Nicosia', 'Limassol', 'Larnaca', 'Paphos']),
            'is_online' => false,
            'registration_url' => 'https://example.com/register',
            'organizer_name' => fake()->company(),
            'is_featured' => false,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ];
    }
}
