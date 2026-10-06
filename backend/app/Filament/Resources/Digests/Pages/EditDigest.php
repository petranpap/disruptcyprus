<?php

namespace App\Filament\Resources\Digests\Pages;

use App\Enums\DigestStatus;
use App\Events\DigestPublished;
use App\Filament\Resources\Digests\DigestResource;
use App\Filament\Support\Translatable;
use App\Models\Digest;
use App\Services\Digests\DigestGenerator;
use App\Services\Digests\GenerationMode;
use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditDigest extends EditRecord
{
    protected static string $resource = DigestResource::class;

    public function getDigest(): Digest
    {
        /** @var Digest $digest */
        $digest = $this->getRecord();

        return $digest;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate')
                ->label(__('admin.digests.regenerate'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->visible(fn () => $this->getDigest()->isDraft())
                ->requiresConfirmation()
                ->modalDescription(__('admin.digests.regenerate_confirm'))
                ->action(function (): void {
                    $digest = $this->getDigest();
                    $reference = CarbonImmutable::parse($digest->period_start->toDateString(), DigestPeriod::timezone());

                    app(DigestGenerator::class)->generate($digest->kind, $digest->cadence, $reference, GenerationMode::Overwrite);

                    $this->getRecord()->refresh();
                    $this->fillForm();
                    Notification::make()->success()->title(__('admin.digests.regenerated'))->send();
                }),
            Action::make('publish')
                ->label(__('admin.digests.publish_notify'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn () => $this->getDigest()->isDraft())
                ->requiresConfirmation()
                ->modalDescription(__('admin.digests.publish_confirm'))
                ->action(function (): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                    $digest = $this->getDigest();
                    $digest->forceFill(['status' => DigestStatus::Published, 'published_at' => now()])->save();
                    DigestPublished::dispatch($digest);

                    Notification::make()->success()->title(__('admin.digests.published_notified'))->send();
                    $this->redirect(DigestResource::getUrl('index'));
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Translatable::clean($data, ['intro']);
    }

    /**
     * Any editor save marks a draft as edited so the scheduler never overwrites it.
     */
    protected function afterSave(): void
    {
        $digest = $this->getDigest();

        if ($digest->isDraft()) {
            $digest->forceFill(['edited_at' => now()])->saveQuietly();
        }
    }
}
