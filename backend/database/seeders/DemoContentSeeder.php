<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Enums\DigestStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Digest;
use App\Models\Event;
use App\Models\Industry;
use App\Models\Section;
use App\Models\User;
use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\PlaceholderImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo articles, events and digests. Dates are relative to "now" so the feeds,
 * "this week" and "this month" views always have content after a fresh seed.
 */
class DemoContentSeeder extends Seeder
{
    /** @var Collection<string, Industry> */
    private Collection $industries;

    public function run(): void
    {
        $this->industries = Industry::query()->get()->keyBy('slug');

        $articles = $this->seedArticles();
        $events = $this->seedEvents();
        $this->seedDigests();
        $this->seedBookmarks($articles, $events);
    }

    /**
     * @return list<Article>
     */
    private function seedArticles(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require __DIR__.'/Data/articles.php';
        /** @var array{paragraphs: array<string, array<string, list<string>>>, quotes: list<array<string, string>>} $bodies */
        $bodies = require __DIR__.'/Data/bodies.php';

        $sections = Section::query()->get()->keyBy('slug');
        $authors = Author::query()->orderBy('id')->get()->values();
        $articles = [];

        foreach ($rows as $index => $row) {
            $locales = array_values(array_intersect(['en', 'el'], array_keys($row)));
            $title = $excerpt = $body = [];

            foreach ($locales as $locale) {
                [$title[$locale], $excerpt[$locale]] = $row[$locale];
                $body[$locale] = $this->buildBody($excerpt[$locale], $bodies, $row['section'], $locale, $index);
            }

            $slugSource = $row['en'][0] ?? Str::transliterate($row['el'][0]);

            $article = Article::query()->updateOrCreate(['slug' => Str::slug($slugSource)], [
                'section_id' => $sections[$row['section']]->id,
                'author_id' => $authors[$row['author']]->id,
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => $body,
                'hero_caption' => in_array('en', $locales, true) ? ['en' => 'Illustration: Disrupt Cyprus', 'el' => 'Εικονογράφηση: Disrupt Cyprus'] : ['el' => 'Εικονογράφηση: Disrupt Cyprus'],
                'is_original' => $row['original'] ?? false,
                'is_featured' => $row['featured'] ?? false,
                'status' => ContentStatus::Published,
                'published_at' => now()->subHours($row['hours'])->subMinutes($index * 7 % 50),
            ]);

            $article->forceFill(['view_count' => ($row['featured'] ?? false) ? random_int(6000, 15000) : random_int(150, 5000)])->saveQuietly();

            $article->industries()->sync($this->industryPivot($row['industries']));

            $primaryColor = $this->industries[$row['industries'][0]]->color;
            $article->addMedia(PlaceholderImage::make($primaryColor, $index + 1))->usingFileName($article->slug.'.jpg')->toMediaCollection(Article::HERO_COLLECTION);

            if ($row['attachment'] ?? false) {
                $article->addMedia($this->placeholderPdf())->usingFileName('state-of-the-ecosystem-2026.pdf')->toMediaCollection(Article::ATTACHMENT_COLLECTION);
            }

            $articles[] = $article;
        }

        return $articles;
    }

    /**
     * @param  array{paragraphs: array<string, array<string, list<string>>>, quotes: list<array<string, string>>}  $bodies
     */
    private function buildBody(string $lede, array $bodies, string $section, string $locale, int $index): string
    {
        $pool = $bodies['paragraphs'][$section][$locale];
        $quote = $bodies['quotes'][$index % count($bodies['quotes'])];

        $paragraphs = array_map(fn (string $text) => "<p>{$text}</p>", [
            $lede,
            $pool[$index % count($pool)],
            $pool[($index + 1) % count($pool)],
        ]);

        $blockquote = "<blockquote><p>{$quote[$locale]}</p><cite>{$quote['by']}</cite></blockquote>";

        return implode("\n", [...$paragraphs, $blockquote, '<p>'.$pool[($index + 2) % count($pool)].'</p>', '<p>'.$pool[($index + 3) % count($pool)].'</p>']);
    }

    /**
     * @return list<Event>
     */
    private function seedEvents(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require __DIR__.'/Data/events.php';
        $events = [];

        foreach ($rows as $index => $row) {
            $startsAt = $this->eventStart($row);
            $isOnline = $row['online'] ?? false;

            $event = Event::query()->updateOrCreate(['slug' => Str::slug($row['en'][0])], [
                'title' => ['en' => $row['en'][0], 'el' => $row['el'][0]],
                'description' => ['en' => $row['en'][1], 'el' => $row['el'][1]],
                'starts_at' => $startsAt->utc(),
                'ends_at' => $startsAt->addHours($row['hours'])->utc(),
                'timezone' => DigestPeriod::timezone(),
                'location_name' => $isOnline ? null : $row['venue'],
                'address' => $isOnline ? null : $row['venue'].', '.$row['city'],
                'city' => $isOnline ? null : $row['city'],
                'is_online' => $isOnline,
                'online_url' => $isOnline ? 'https://meet.example.com/'.Str::slug($row['en'][0]) : null,
                'registration_url' => 'https://example.com/events/'.Str::slug($row['en'][0]),
                'organizer_name' => 'Disrupt Cyprus Community',
                'price_info' => $row['price'],
                'is_featured' => $row['featured'] ?? false,
                'status' => ContentStatus::Published,
                'published_at' => now()->subDays(30),
            ]);

            $event->forceFill(['view_count' => random_int(50, 2500)])->saveQuietly();
            $event->industries()->sync($this->industryPivot($row['industries']));
            $event->addMedia(PlaceholderImage::make($this->industries[$row['industries'][0]]->color, 100 + $index))->usingFileName($event->slug.'.jpg')->toMediaCollection(Event::HERO_COLLECTION);

            $events[] = $event;
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function eventStart(array $row): CarbonImmutable
    {
        $now = CarbonImmutable::now(DigestPeriod::timezone());

        $day = match ($row['when']) {
            'this_week' => $now->addDays((int) round($row['slot'] * (7 - $now->dayOfWeekIso))),
            'this_month' => $this->dayInRestOfMonth($now, (float) $row['slot']),
            default => $now->addDays((int) $row['days']),
        };

        $start = $day->setTime((int) $row['hour'], 0);

        // Keep "upcoming" events in the future even when seeding late in the day.
        return $row['when'] !== 'past' && $start->lessThanOrEqualTo($now) ? $now->addHours(2)->startOfHour() : $start;
    }

    private function dayInRestOfMonth(CarbonImmutable $now, float $slot): CarbonImmutable
    {
        $from = $now->endOfWeek()->addDay()->startOfDay();
        $to = $now->endOfMonth()->startOfDay();

        // Late in the month there is nothing after this week: spread over the next three weeks instead.
        if ($from->greaterThan($to)) {
            $to = $from->addWeeks(3);
        }

        return $from->addDays((int) round($slot * $from->diffInDays($to)));
    }

    private function seedDigests(): void
    {
        $today = CarbonImmutable::now(DigestPeriod::timezone());

        // Yesterday's Daily News (published) and today's draft awaiting an editor.
        $this->digest(DigestPeriod::for(DigestKind::News, DigestCadence::Daily, $today->subDay()), DigestStatus::Published);
        $this->digest(DigestPeriod::for(DigestKind::News, DigestCadence::Daily, $today), DigestStatus::Draft);
        $this->digest(DigestPeriod::for(DigestKind::News, DigestCadence::Monthly, $today->subMonthNoOverflow()), DigestStatus::Published);
        $this->digest(DigestPeriod::for(DigestKind::Events, DigestCadence::Weekly, $today), DigestStatus::Published);
        $this->digest(DigestPeriod::for(DigestKind::Events, DigestCadence::Monthly, $today), DigestStatus::Published);
    }

    private function digest(DigestPeriod $period, DigestStatus $status): void
    {
        [$from, $to] = $period->window();

        $items = $period->kind === DigestKind::News
            ? Article::query()->published()
                ->whereBetween('published_at', [$from, $to])
                ->orderByDesc('is_featured')->orderByDesc('view_count')
                ->limit($period->cadence === DigestCadence::Daily ? 6 : 12)->get()
            : Event::query()->published()
                ->where('starts_at', '>=', $from)->where('starts_at', '<', $to)
                ->orderBy('starts_at')->get();

        // A quiet day still gets a readable demo digest.
        if ($items->isEmpty() && $period->kind === DigestKind::News) {
            $items = Article::query()->published()->latest('published_at')->limit(5)->get();
        }

        $intro = $period->kind === DigestKind::News
            ? ['en' => 'The stories our editors picked for you.', 'el' => 'Οι ιστορίες που επέλεξαν για σένα οι συντάκτες μας.']
            : ['en' => 'Where to be: the ecosystem’s events at a glance.', 'el' => 'Πού να βρεθείς: οι εκδηλώσεις του οικοσυστήματος με μια ματιά.'];

        $digest = Digest::query()->updateOrCreate(
            ['kind' => $period->kind, 'cadence' => $period->cadence, 'period_start' => $period->start->toDateString()],
            [
                'period_end' => $period->end->toDateString(),
                'slug' => $period->slug(),
                'title' => $period->titles(),
                'intro' => $intro,
                'status' => $status,
                'published_at' => $status === DigestStatus::Published ? now()->subHours(2) : null,
                'generated_automatically' => true,
            ],
        );

        $digest->items()->delete();

        foreach ($items->values() as $position => $item) {
            $digest->items()->create([
                'itemable_type' => $item->getMorphClass(),
                'itemable_id' => $item->getKey(),
                'position' => $position + 1,
                'editor_note' => $position === 0 ? ['en' => 'Our top pick.', 'el' => 'Η κορυφαία μας επιλογή.'] : null,
            ]);
        }
    }

    /**
     * @param  list<Article>  $articles
     * @param  list<Event>  $events
     */
    private function seedBookmarks(array $articles, array $events): void
    {
        $reader = User::query()->where('email', config('app.seed_accounts.reader'))->first();

        if ($reader === null) {
            return;
        }

        foreach ([$articles[0], $articles[14], $articles[24], $events[3], $events[7]] as $item) {
            $reader->bookmarks()->firstOrCreate([
                'bookmarkable_type' => $item->getMorphClass(),
                'bookmarkable_id' => $item->getKey(),
            ]);
        }
    }

    /**
     * @param  list<string>  $slugs  First slug is the primary industry.
     * @return array<int, array{is_primary: bool}>
     */
    private function industryPivot(array $slugs): array
    {
        $pivot = [];

        foreach ($slugs as $position => $slug) {
            $pivot[$this->industries[$slug]->id] = ['is_primary' => $position === 0];
        }

        return $pivot;
    }

    private function placeholderPdf(): string
    {
        $content = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            ."4 0 obj<</Length 66>>stream\nBT /F1 24 Tf 72 760 Td (State of the Ecosystem 2026 - demo) Tj ET\nendstream endobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'report').'.pdf';
        file_put_contents($path, $content);

        return $path;
    }
}
