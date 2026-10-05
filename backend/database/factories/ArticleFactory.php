<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleEn = rtrim(fake('en_US')->sentence(8), '.');

        return [
            'section_id' => Section::factory(),
            'author_id' => Author::factory(),
            'slug' => Str::slug($titleEn).'-'.Str::lower(Str::random(5)),
            'title' => ['en' => $titleEn, 'el' => rtrim(fake('el_GR')->sentence(8), '.')],
            'excerpt' => ['en' => fake('en_US')->sentence(20), 'el' => fake('el_GR')->sentence(20)],
            'body' => [
                'en' => '<p>'.implode('</p><p>', fake('en_US')->paragraphs(5)).'</p>',
                'el' => '<p>'.implode('</p><p>', fake('el_GR')->paragraphs(5)).'</p>',
            ],
            'is_original' => false,
            'is_featured' => false,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHours(fake()->numberBetween(1, 72)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ContentStatus::Draft, 'published_at' => null]);
    }

    /**
     * Content only in the given locale.
     */
    public function onlyIn(string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => [$locale => $attributes['title'][$locale]],
            'excerpt' => [$locale => $attributes['excerpt'][$locale]],
            'body' => [$locale => $attributes['body'][$locale]],
        ]);
    }
}
