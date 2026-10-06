<?php

namespace App\Filament\Resources\Digests\Pages;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Filament\Resources\Digests\DigestResource;
use App\Filament\Resources\Digests\Schemas\DigestForm;
use App\Services\Digests\DigestGenerator;
use App\Services\Digests\GenerationOutcome;
use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Creating a digest runs the same generator as the scheduler, so a manual digest starts pre-filled
 * and can never duplicate a period.
 */
class CreateDigest extends CreateRecord
{
    protected static string $resource = DigestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $kind = DigestKind::from((string) $data['kind']);
        $cadence = DigestCadence::from((string) $data['cadence']);
        $reference = CarbonImmutable::parse((string) $data['reference_date'], DigestPeriod::timezone());

        if (! in_array($cadence->value, DigestForm::SUPPORTED[$kind->value], true)) {
            Notification::make()->danger()->title(__('admin.digests.invalid_combination'))->send();
            $this->halt();
        }

        $result = app(DigestGenerator::class)->generate($kind, $cadence, $reference);

        if ($result->outcome !== GenerationOutcome::Created) {
            Notification::make()->warning()->title(__('admin.digests.exists'))->send();
            $this->redirect(DigestResource::getUrl('edit', ['record' => $result->digest]));
            $this->halt();
        }

        return $result->digest;
    }
}
