<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            ['slug' => 'news', 'name' => ['en' => 'News', 'el' => 'Ειδήσεις'], 'has_articles' => true],
            ['slug' => 'startups', 'name' => ['en' => 'Startups', 'el' => 'Startups'], 'has_articles' => true],
            ['slug' => 'research', 'name' => ['en' => 'Research', 'el' => 'Έρευνα'], 'has_articles' => true],
            ['slug' => 'investors', 'name' => ['en' => 'Investors', 'el' => 'Επενδυτές'], 'has_articles' => true],
            ['slug' => 'events', 'name' => ['en' => 'Events', 'el' => 'Εκδηλώσεις'], 'has_articles' => false],
        ];

        foreach ($sections as $index => $section) {
            Section::query()->updateOrCreate(
                ['slug' => $section['slug']],
                [...$section, 'sort_order' => ($index + 1) * 10],
            );
        }
    }
}
