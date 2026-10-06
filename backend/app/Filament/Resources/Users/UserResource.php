<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Read-mostly: admins can search, export and change roles. Accounts are created by readers themselves.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'administration';

    public static function getModelLabel(): string
    {
        return __('admin.users.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.plural');
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => __('admin.users.roles.'.$role->value)])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(2)->schema([
                TextInput::make('name')->label(__('admin.common.name'))->disabled()->dehydrated(false),
                TextInput::make('email')->label(__('admin.users.email'))->disabled()->dehydrated(false),
                Select::make('role')
                    ->label(__('admin.users.role'))
                    ->options(self::roleOptions())
                    ->required()
                    // Admins cannot demote themselves and lock everyone out.
                    ->disabled(fn (?User $record) => $record !== null && $record->is(auth()->user())),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('industries'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('admin.common.name'))->searchable(),
                TextColumn::make('email')->label(__('admin.users.email'))->searchable()->copyable(),
                TextColumn::make('role')->label(__('admin.users.role'))->badge()
                    ->formatStateUsing(fn (UserRole $state) => __('admin.users.roles.'.$state->value))
                    ->color(fn (UserRole $state) => match ($state) {
                        UserRole::Admin => 'danger',
                        UserRole::Editor => 'info',
                        UserRole::Reader => 'gray',
                    }),
                IconColumn::make('verified')->label(__('admin.users.email_verified'))->boolean()->state(fn (User $record) => $record->email_verified_at !== null),
                TextColumn::make('locale')->label(__('admin.users.locale'))->formatStateUsing(fn (string $state) => strtoupper($state)),
                TextColumn::make('industries_count')->label(__('admin.users.industries_count'))->numeric()->sortable(),
                TextColumn::make('created_at')->label(__('admin.users.joined'))->date('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label(__('admin.users.role'))->options(self::roleOptions()),
                TernaryFilter::make('email_verified_at')->label(__('admin.users.email_verified'))->nullable(),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()]);
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<User> $query */
        $query = parent::getEloquentQuery();

        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
