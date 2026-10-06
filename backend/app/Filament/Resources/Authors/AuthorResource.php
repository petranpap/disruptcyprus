<?php

namespace App\Filament\Resources\Authors;

use App\Enums\UserRole;
use App\Filament\Resources\Authors\Pages\CreateAuthor;
use App\Filament\Resources\Authors\Pages\EditAuthor;
use App\Filament\Resources\Authors\Pages\ListAuthors;
use App\Filament\Support\Translatable;
use App\Models\Author;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'content';

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return __('admin.authors.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.authors.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(2)->schema([
                TextInput::make('name')->label(__('admin.common.name'))->required()->maxLength(120),
                Select::make('user_id')
                    ->label(__('admin.authors.user'))
                    ->options(fn () => User::query()->whereIn('role', [UserRole::Editor, UserRole::Admin])->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                Translatable::tabs(fn (string $locale) => [
                    TextInput::make("title.{$locale}")->label(__('admin.authors.title'))->maxLength(120),
                    Textarea::make("bio.{$locale}")->label(__('admin.authors.bio'))->rows(3)->maxLength(600),
                ]),
                Toggle::make('is_verified')->label(__('admin.authors.is_verified')),
                SpatieMediaLibraryFileUpload::make('avatar')->label(__('admin.authors.avatar'))->collection(Author::AVATAR_COLLECTION)->image()->avatar()->maxSize(4096),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('articles'))
            ->defaultSort('name')
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')->label('')->collection(Author::AVATAR_COLLECTION)->conversion('thumb')->circular()->imageSize(36),
                TextColumn::make('name')->label(__('admin.common.name'))->searchable(),
                TextColumn::make('title')->label(__('admin.authors.title')),
                IconColumn::make('is_verified')->label(__('admin.authors.is_verified'))->boolean(),
                TextColumn::make('articles_count')->label(__('admin.authors.articles'))->numeric()->sortable(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuthors::route('/'),
            'create' => CreateAuthor::route('/create'),
            'edit' => EditAuthor::route('/{record}/edit'),
        ];
    }
}
