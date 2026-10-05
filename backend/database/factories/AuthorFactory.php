<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'title' => ['en' => 'Staff Writer', 'el' => 'Συντάκτρια'],
            'bio' => ['en' => fake('en_US')->sentence(12), 'el' => fake('el_GR')->sentence(12)],
            'is_verified' => true,
        ];
    }
}
