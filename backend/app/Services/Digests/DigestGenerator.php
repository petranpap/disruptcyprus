<?php

namespace App\Services\Digests;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Enums\DigestStatus;
use App\Models\Article;
use App\Models\Digest;
use App\Models\Event;
use App\Models\Section;
use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Builds DRAFT digests for editors to review. Idempotent per (kind, cadence, period_start):
 * never duplicates, never touches published digests, never overwrites an edited draft unless asked.
 *
 * Selection rules:
 *  - Daily News:     articles published in [D-1 06:00, D 06:00), featured first, then most read
 *  - Weekly Events:  events starting Monday–Sunday of the period, chronological
 *  - Monthly News:   top articles of the month per section (sections in menu order), most read first
 *  - Monthly Events: events starting in the month, chronological
 */
class DigestGenerator
{
    public const DAILY_LIMIT = 15;

    public const MONTHLY_PER_SECTION = 5;

    public const EVENTS_LIMIT = 50;

    public function generate(DigestKind $kind, DigestCadence $cadence, CarbonImmutable $reference, GenerationMode $mode = GenerationMode::CreateOnly): GenerationResult
    {
        $period = DigestPeriod::for($kind, $cadence, $reference);
        $existing = $this->find($period);

        if ($existing !== null) {
            $skip = match (true) {
                $existing->status === DigestStatus::Published => GenerationOutcome::SkippedPublished,
                $mode === GenerationMode::CreateOnly => GenerationOutcome::SkippedExisting,
                $existing->edited_at !== null && $mode !== GenerationMode::Overwrite => GenerationOutcome::SkippedEdited,
                default => null,
            };

            if ($skip !== null) {
                return new GenerationResult($skip, $existing);
            }

            return new GenerationResult(GenerationOutcome::Refreshed, $this->fill($existing, $period));
        }

        try {
            $digest = DB::transaction(fn () => $this->fill(new Digest([
                'kind' => $kind,
                'cadence' => $cadence,
                'period_start' => $period->start->toDateString(),
                'period_end' => $period->end->toDateString(),
                'status' => DigestStatus::Draft,
                'generated_automatically' => true,
            ]), $period));
        } catch (UniqueConstraintViolationException) {
            // Another run created it meanwhile.
            return new GenerationResult(GenerationOutcome::SkippedExisting, $this->find($period) ?? throw new \LogicException('Digest vanished'));
        }

        return new GenerationResult(GenerationOutcome::Created, $digest);
    }

    /**
     * Reference date the scheduler uses for each digest when it runs at `$now`.
     */
    public static function defaultReference(DigestKind $kind, DigestCadence $cadence, CarbonImmutable $now): CarbonImmutable
    {
        $local = $now->setTimezone(DigestPeriod::timezone());

        return match ([$kind, $cadence]) {
            // Sunday evening run prepares next week (works for manual mid-week runs too).
            [DigestKind::Events, DigestCadence::Weekly] => $local->addWeek()->startOfWeek(),
            // 1st of the month: news looks back at the previous month.
            [DigestKind::News, DigestCadence::Monthly] => $local->subMonthNoOverflow(),
            default => $local,
        };
    }

    private function find(DigestPeriod $period): ?Digest
    {
        return Digest::query()
            ->where('kind', $period->kind)
            ->where('cadence', $period->cadence)
            ->whereDate('period_start', $period->start->toDateString())
            ->first();
    }

    private function fill(Digest $digest, DigestPeriod $period): Digest
    {
        $digest->slug = $period->slug();
        $digest->setTranslations('title', $period->titles());
        $digest->edited_at = null;
        $digest->save();

        $digest->items()->delete();

        foreach ($this->select($period)->values() as $index => $item) {
            $digest->items()->create([
                'itemable_type' => $item->getMorphClass(),
                'itemable_id' => $item->getKey(),
                'position' => $index + 1,
            ]);
        }

        return $digest->refresh();
    }

    /**
     * @return Collection<int, Article>|Collection<int, Event>
     */
    public function select(DigestPeriod $period): Collection
    {
        [$from, $to] = $period->window();

        return match ([$period->kind, $period->cadence]) {
            [DigestKind::News, DigestCadence::Daily] => Article::query()->published()
                ->where('published_at', '>=', $from)->where('published_at', '<', $to)
                ->orderByDesc('is_featured')->orderByDesc('view_count')->orderByDesc('published_at')
                ->limit(self::DAILY_LIMIT)
                ->get(),
            [DigestKind::News, DigestCadence::Monthly] => $this->topArticlesPerSection($from, $to),
            default => Event::query()->published()
                ->where('starts_at', '>=', $from)->where('starts_at', '<', $to)
                ->orderBy('starts_at')
                ->limit(self::EVENTS_LIMIT)
                ->get(),
        };
    }

    /**
     * @return Collection<int, Article>
     */
    private function topArticlesPerSection(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $articles = new Collection;

        foreach (Section::query()->where('has_articles', true)->ordered()->get() as $section) {
            $articles = $articles->merge(
                Article::query()->published()
                    ->where('section_id', $section->id)
                    ->where('published_at', '>=', $from)->where('published_at', '<', $to)
                    ->orderByDesc('view_count')->orderByDesc('published_at')
                    ->limit(self::MONTHLY_PER_SECTION)
                    ->get()
            );
        }

        return $articles;
    }
}
