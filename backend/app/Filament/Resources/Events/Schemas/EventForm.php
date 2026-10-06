<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Filament\Resources\Articles\Schemas\ArticleForm;
use App\Filament\Support\ContentStatusOptions;
use App\Filament\Support\IndustryFields;
use App\Filament\Support\Translatable;
use App\Models\Event;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EventForm
{
    /** @var list<string> */
    public const CITIES = ['Nicosia', 'Limassol', 'Larnaca', 'Paphos', 'Famagusta', 'Troodos'];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make()->columnSpan(2)->schema([
                    Section::make(__('admin.common.translations'))
                        ->description(__('admin.events.locale_hint'))
                        ->schema([
                            Translatable::tabs(fn (string $locale) => [
                                TextInput::make("title.{$locale}")
                                    ->label(__('admin.common.title'))
                                    ->maxLength(200)
                                    ->requiredWithout('title.'.Translatable::other($locale)),
                                RichEditor::make("description.{$locale}")
                                    ->label(__('admin.events.description'))
                                    ->toolbarButtons(ArticleForm::TOOLBAR)
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('editor')
                                    ->fileAttachmentsVisibility('public'),
                                TextInput::make("price_info.{$locale}")
                                    ->label(__('admin.events.price_info'))
                                    ->maxLength(120),
                            ]),
                        ]),
                    Section::make(__('admin.events.when'))->columns(2)->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__('admin.events.starts_at'))
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label(__('admin.events.ends_at'))
                            ->seconds(false)
                            ->after('starts_at'),
                    ]),
                    Section::make(__('admin.events.where'))->columns(2)->schema([
                        Toggle::make('is_online')->label(__('admin.events.is_online'))->live()->columnSpanFull(),
                        TextInput::make('location_name')->label(__('admin.events.location_name'))->maxLength(255)->hidden(fn (Get $get) => (bool) $get('is_online')),
                        TextInput::make('city')->label(__('admin.events.city'))->datalist(self::CITIES)->maxLength(96)->hidden(fn (Get $get) => (bool) $get('is_online')),
                        TextInput::make('address')->label(__('admin.events.address'))->maxLength(255)->columnSpanFull()->hidden(fn (Get $get) => (bool) $get('is_online')),
                        TextInput::make('online_url')->label(__('admin.events.online_url'))->url()->maxLength(2048)->columnSpanFull()
                            ->required(fn (Get $get) => (bool) $get('is_online'))
                            ->visible(fn (Get $get) => (bool) $get('is_online')),
                        TextInput::make('registration_url')->label(__('admin.events.registration_url'))->url()->maxLength(2048)->columnSpanFull(),
                        TextInput::make('organizer_name')->label(__('admin.events.organizer_name'))->maxLength(255)->columnSpanFull(),
                    ]),
                    Section::make(__('admin.common.image'))->schema([
                        SpatieMediaLibraryFileUpload::make('hero')
                            ->hiddenLabel()
                            ->collection(Event::HERO_COLLECTION)
                            ->image()
                            ->maxSize(8192),
                    ]),
                ]),
                Group::make()->columnSpan(1)->schema([
                    Section::make(__('admin.common.publishing'))->schema([
                        Select::make('status')
                            ->label(__('admin.common.status'))
                            ->options(ContentStatusOptions::options())
                            ->default('draft')
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label(__('admin.common.published_at'))
                            ->helperText(__('admin.common.published_at_help'))
                            ->seconds(false),
                        Toggle::make('is_featured')->label(__('admin.common.featured')),
                    ]),
                    Section::make(__('admin.common.details'))->schema([
                        ...IndustryFields::make(),
                        TextInput::make('slug')
                            ->label(__('admin.common.slug'))
                            ->helperText(__('admin.common.slug_help'))
                            ->alphaDash()
                            ->maxLength(191),
                    ]),
                ]),
            ]),
        ]);
    }
}
