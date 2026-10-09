<?php

namespace App\Filament\Resources\WaitlistSignups;

use App\Filament\Resources\WaitlistSignups\Pages\ListWaitlistSignups;
use App\Models\WaitlistSignup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * People who joined the waitlist on the coming-soon page. Admins only (WaitlistSignupPolicy): view, export, delete.
 */
class WaitlistSignupResource extends Resource
{
    protected static ?string $model = WaitlistSignup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'engagement';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'waitlist';

    public static function getModelLabel(): string
    {
        return __('admin.waitlist.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.waitlist.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) WaitlistSignup::query()->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('admin.waitlist.name'))->searchable(),
                TextColumn::make('email')->label(__('admin.waitlist.email'))->searchable()->copyable(),
                TextColumn::make('locale')->label(__('admin.waitlist.locale'))->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => strtoupper($state)),
                TextColumn::make('created_at')->label(__('admin.waitlist.joined'))->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('locale')->label(__('admin.waitlist.locale'))->options(['el' => 'EL', 'en' => 'EN']),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWaitlistSignups::route('/'),
        ];
    }
}
