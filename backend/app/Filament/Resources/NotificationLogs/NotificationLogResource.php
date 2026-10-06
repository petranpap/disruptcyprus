<?php

namespace App\Filament\Resources\NotificationLogs;

use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use UnitEnum;

/**
 * Read-only log of in-app notifications. Phase 6 adds push delivery details.
 */
class NotificationLogResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'engagement';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'notification-log';

    public static function getModelLabel(): string
    {
        return __('admin.notifications.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.notifications.plural');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role->canAccessAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('notifiable'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('data.type')->label(__('admin.notifications.type'))->badge()->color('gray'),
                TextColumn::make('data.title')->label(__('admin.notifications.title'))->wrap()->limit(80),
                TextColumn::make('notifiable.email')->label(__('admin.notifications.recipient')),
                TextColumn::make('created_at')->label(__('admin.notifications.created_at'))->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('read_at')->label(__('admin.notifications.read_at'))->dateTime('d M Y H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.notifications.type'))
                    ->options(fn () => DatabaseNotification::query()->distinct()->pluck('data->type', 'data->type')->filter()->all())
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $inner, $type) => $inner->where('data->type', $type))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationLogs::route('/'),
        ];
    }
}
