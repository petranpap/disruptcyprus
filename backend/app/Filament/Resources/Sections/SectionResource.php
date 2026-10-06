<?php

namespace App\Filament\Resources\Sections;

use App\Filament\Resources\Sections\Pages\EditSection;
use App\Filament\Resources\Sections\Pages\ListSections;
use App\Filament\Support\Translatable;
use App\Models\Section as SectionModel;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The five menu sections are fixed (the app routes on their slugs): editable names and order only.
 */
class SectionResource extends Resource
{
    protected static ?string $model = SectionModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'taxonomy';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('admin.sections.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.sections.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->schema([
                Translatable::tabs(fn (string $locale) => [
                    TextInput::make("name.{$locale}")->label(__('admin.common.name'))->required()->maxLength(40),
                ]),
                TextInput::make('slug')->label(__('admin.common.slug'))->disabled()->dehydrated(false),
                Toggle::make('has_articles')->label(__('admin.sections.has_articles'))->helperText(__('admin.sections.has_articles_help'))->disabled()->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('admin.common.name')),
                TextColumn::make('slug')->label(__('admin.common.slug'))->color('gray'),
                IconColumn::make('has_articles')->label(__('admin.sections.has_articles'))->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSections::route('/'),
            'edit' => EditSection::route('/{record}/edit'),
        ];
    }
}
