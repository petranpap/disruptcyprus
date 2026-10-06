<?php

namespace App\Console\Commands;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Services\Digests\DigestGenerator;
use App\Services\Digests\GenerationMode;
use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('digests:generate
    {kind : news or events}
    {cadence : daily, weekly or monthly}
    {--date= : Reference date in Nicosia time (YYYY-MM-DD); defaults to the scheduler\'s period}
    {--refresh : Rebuild an existing draft unless an editor has edited it}')]
#[Description('Create the draft digest for a period (idempotent)')]
class GenerateDigest extends Command
{
    public function handle(DigestGenerator $generator): int
    {
        $kind = DigestKind::tryFrom((string) $this->argument('kind'));
        $cadence = DigestCadence::tryFrom((string) $this->argument('cadence'));

        if ($kind === null || $cadence === null) {
            $this->error('Unknown digest kind or cadence.');

            return self::INVALID;
        }

        $date = $this->option('date');
        $reference = is_string($date)
            ? CarbonImmutable::createFromFormat('!Y-m-d', $date, DigestPeriod::timezone())
            : DigestGenerator::defaultReference($kind, $cadence, CarbonImmutable::now());

        try {
            $result = $generator->generate($kind, $cadence, $reference, $this->option('refresh') ? GenerationMode::RefreshUnedited : GenerationMode::CreateOnly);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $this->info(sprintf('%s: %s (%d items)', $result->outcome->value, $result->digest->slug, $result->digest->items()->count()));

        return self::SUCCESS;
    }
}
