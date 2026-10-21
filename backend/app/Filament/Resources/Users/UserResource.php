<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Admins manage accounts: create staff (editors, admins), edit anyone's name, email, role, language and password.
 * Readers usually sign up themselves. Deleted (anonymized) accounts are read-only.
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
        $readOnly = fn (?User $record) => $record?->trashed() ?? false;

        return $schema->components([
            Section::make(__('admin.users.account'))->columnSpanFull()->columns(2)->disabled($readOnly)->schema([
                TextInput::make('name')->label(__('admin.common.name'))->trim()->required()->maxLength(120),
                TextInput::make('email')->label(__('admin.users.email'))->trim()->email()->required()->maxLength(191)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (string $state) => mb_strtolower($state)),
                Select::make('role')
                    ->label(__('admin.users.role'))
                    ->options(self::roleOptions())
                    ->default(UserRole::Editor->value)
                    ->required()
                    ->helperText(__('admin.users.role_help'))
                    // Admins cannot demote themselves and lock everyone out.
                    ->disabled(fn (?User $record) => $record !== null && $record->is(auth()->user())),
                Select::make('locale')->label(__('admin.users.locale'))->options(['en' => 'English', 'el' => 'Ελληνικά'])->default('en')->required(),
            ]),
            Section::make(__('admin.users.password_section'))->columnSpanFull()->columns(2)->disabled($readOnly)
                ->description(fn (string $operation) => $operation === 'edit' ? __('admin.users.password_help') : null)
                ->schema([
                    TextInput::make('password')->label(__('admin.users.password'))
                        ->password()->revealable()
                        ->rule(Password::defaults())
                        ->required(fn (string $operation) => $operation === 'create')
                        ->confirmed()
                        // Empty on edit = keep the current password. The model's `hashed` cast hashes it.
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->autocomplete('new-password'),
                    TextInput::make('password_confirmation')->label(__('admin.users.password_confirmation'))
                        ->password()->revealable()
                        ->required(fn (string $operation, Get $get) => $operation === 'create' || filled($get('password')))
                        ->dehydrated(false)
                        ->autocomplete('new-password'),
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

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
