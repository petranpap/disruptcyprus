<?php

namespace App\Filament\Resources\WaitlistSignups\Pages;

use App\Filament\Resources\WaitlistSignups\WaitlistSignupResource;
use App\Models\WaitlistSignup;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListWaitlistSignups extends ListRecords
{
    protected static string $resource = WaitlistSignupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('admin.waitlist.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    /**
     * CSV for the launch email (UTF-8 with BOM so Excel shows Greek names correctly).
     */
    private function exportCsv(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\u{FEFF}");
            fputcsv($out, ['name', 'email', 'locale', 'joined_at'], escape: '');
            WaitlistSignup::query()->orderBy('id')->each(function (WaitlistSignup $signup) use ($out): void {
                fputcsv($out, [$this->safe($signup->name), $signup->email, $signup->locale, $signup->created_at?->toIso8601String()], escape: '');
            });
            fclose($out);
        }, 'disrupt-cyprus-waitlist-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Names are typed by the public: neutralise spreadsheet formulas (CSV injection).
     */
    private function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
