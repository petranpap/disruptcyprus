<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

/**
 * Fictional editorial team for demo content.
 */
class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $authors = [
            ['Elena Vassiliou', 'Lead Tech Editor', 'Επικεφαλής Συντάκτρια Τεχνολογίας', true],
            ['Andreas Charalambous', 'Venture & Markets Reporter', 'Συντάκτης Επενδύσεων & Αγορών', true],
            ['Maria Georgiou', 'Research Correspondent', 'Ανταποκρίτρια Έρευνας', true],
            ['Nikos Petrides', 'Startups Editor', 'Συντάκτης Startups', true],
            ['Sofia Antoniou', 'Policy & GovTech Reporter', 'Συντάκτρια Πολιτικής & GovTech', false],
            ['Christos Ioannou', 'Contributing Writer', 'Συνεργάτης Συντάκτης', false],
        ];

        foreach ($authors as [$name, $titleEn, $titleEl, $verified]) {
            Author::query()->updateOrCreate(['name' => $name], [
                'title' => ['en' => $titleEn, 'el' => $titleEl],
                'bio' => [
                    'en' => "{$name} covers the Cyprus and Eastern Mediterranean innovation ecosystem for Disrupt Cyprus.",
                    'el' => 'Καλύπτει το οικοσύστημα καινοτομίας της Κύπρου και της Ανατολικής Μεσογείου για το Disrupt Cyprus.',
                ],
                'is_verified' => $verified,
            ]);
        }
    }
}
