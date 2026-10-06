<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('admin.users.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => self::exportCsv()),
        ];
    }

    /**
     * Streams active accounts as CSV (no passwords or tokens; deleted accounts excluded).
     */
    public static function exportCsv(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, ['id', 'name', 'email', 'role', 'locale', 'email_verified', 'industries', 'joined']);

            User::query()->withCount('industries')->orderBy('id')->chunk(500, function ($users) use ($output): void {
                foreach ($users as $user) {
                    /** @var User $user */
                    fputcsv($output, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->role->value,
                        $user->locale,
                        $user->email_verified_at !== null ? 'yes' : 'no',
                        $user->getAttribute('industries_count'),
                        $user->created_at?->toDateString(),
                    ]);
                }
            });

            fclose($output);
        }, 'disrupt-cyprus-users-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
