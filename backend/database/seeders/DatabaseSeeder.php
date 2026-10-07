<?php

namespace Database\Seeders;

use App\Events\ContentPublished;
use App\Events\DigestPublished;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SectionSeeder::class,
            IndustrySeeder::class,
            AuthorSeeder::class,
            UserSeeder::class,
        ]);

        if (! app()->isProduction()) {
            // Demo content must not notify the demo readers (featured-article alerts, digest pushes).
            Event::fakeFor(fn () => $this->call(DemoContentSeeder::class), [ContentPublished::class, DigestPublished::class]);
        }
    }
}
