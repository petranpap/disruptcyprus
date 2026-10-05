<?php

namespace App\Support;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * The period a digest covers, computed in the business timezone (Asia/Nicosia).
 *
 *  - Daily News:     keyed by day D, covers [D-1 06:00, D 06:00)
 *  - Weekly Events:  Monday–Sunday of the week
 *  - Monthly News:   the calendar month (generated on the 1st of the next month)
 *  - Monthly Events: the calendar month (generated on its 1st)
 */
final readonly class DigestPeriod
{
    public const DAILY_CUTOFF_HOUR = 6;

    private function __construct(
        public DigestKind $kind,
        public DigestCadence $cadence,
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public static function for(DigestKind $kind, DigestCadence $cadence, CarbonImmutable $reference): self
    {
        $day = $reference->setTimezone(self::timezone())->startOfDay();

        return match ([$kind, $cadence]) {
            [DigestKind::News, DigestCadence::Daily] => new self($kind, $cadence, $day, $day),
            [DigestKind::Events, DigestCadence::Weekly] => new self($kind, $cadence, $day->startOfWeek(), $day->endOfWeek()->startOfDay()),
            [DigestKind::News, DigestCadence::Monthly],
            [DigestKind::Events, DigestCadence::Monthly] => new self($kind, $cadence, $day->startOfMonth(), $day->endOfMonth()->startOfDay()),
            default => throw new InvalidArgumentException("Unsupported digest: {$kind->value}/{$cadence->value}"),
        };
    }

    public static function timezone(): string
    {
        return (string) config('app.business_timezone');
    }

    /**
     * Content window as UTC instants [from, to).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function window(): array
    {
        if ($this->cadence === DigestCadence::Daily) {
            $to = $this->start->setTime(self::DAILY_CUTOFF_HOUR, 0);

            return [$to->subDay()->utc(), $to->utc()];
        }

        return [$this->start->utc(), $this->end->addDay()->utc()];
    }

    public function slug(): string
    {
        return match ($this->cadence) {
            DigestCadence::Daily => "daily-{$this->kind->value}-{$this->start->format('Y-m-d')}",
            DigestCadence::Weekly => "weekly-{$this->kind->value}-{$this->start->format('o-\WW')}",
            DigestCadence::Monthly => "monthly-{$this->kind->value}-{$this->start->format('Y-m')}",
        };
    }

    /**
     * @return array{en: string, el: string}
     */
    public function titles(): array
    {
        return ['en' => $this->title('en'), 'el' => $this->title('el')];
    }

    private function title(string $locale): string
    {
        $start = $this->start->locale($locale);
        $end = $this->end->locale($locale);

        return match ([$this->kind, $this->cadence]) {
            [DigestKind::News, DigestCadence::Daily] => ($locale === 'el' ? 'Ημερήσια Ενημέρωση' : 'Daily News').' — '.$start->translatedFormat('j F Y'),
            [DigestKind::Events, DigestCadence::Weekly] => ($locale === 'el' ? 'Εκδηλώσεις της εβδομάδας' : 'Events this week').' — '
                .($start->month === $end->month ? $start->format('j') : $start->translatedFormat('j F')).'–'.$end->translatedFormat('j F'),
            [DigestKind::News, DigestCadence::Monthly] => ($locale === 'el' ? 'Μηνιαία Ανασκόπηση' : 'Monthly News').' — '.$start->translatedFormat('F Y'),
            [DigestKind::Events, DigestCadence::Monthly] => ($locale === 'el' ? 'Εκδηλώσεις του μήνα' : 'Events this month').' — '.$start->translatedFormat('F Y'),
            default => throw new InvalidArgumentException('Unsupported digest'),
        };
    }
}
