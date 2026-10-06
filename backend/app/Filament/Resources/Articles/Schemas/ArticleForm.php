<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Filament\Support\ContentStatusOptions;
use App\Filament\Support\IndustryFields;
use App\Filament\Support\Translatable;
use App\Models\Article;
use App\Models\Author;
use App\Models\Section as SectionModel;
use App\Services\ReadingTimeCalculator;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ArticleForm
{
    /**
     * Editor toolbar limited to what the reader renders (and the sanitizer allows).
     *
     * @var list<list<string>>
     */
    public const TOOLBAR = [
        ['bold', 'italic', 'link'],
        ['h2', 'h3'],
        ['blockquote', 'bulletList', 'orderedList'],
        ['attachFiles'],
        ['undo', 'redo'],
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make()->columnSpan(2)->schema([
                    Section::make(__('admin.common.translations'))
                        ->description(__('admin.articles.locale_hint'))
                        ->schema([
                            Translatable::tabs(fn (string $locale) => [
                                TextInput::make("title.{$locale}")
                                    ->label(__('admin.common.title'))
                                    ->maxLength(200)
                                    ->requiredWithout('title.'.Translatable::other($locale)),
                                Textarea::make("excerpt.{$locale}")
                                    ->label(__('admin.articles.excerpt'))
                                    ->rows(3)
                                    ->maxLength(400),
                                RichEditor::make("body.{$locale}")
                                    ->label(__('admin.articles.body'))
                                    ->toolbarButtons(self::TOOLBAR)
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('editor')
                                    ->fileAttachmentsVisibility('public')
                                    ->live(debounce: 1500),
                                Text::make(fn (Get $get) => __('admin.articles.reading_time').': '.__('admin.articles.reading_time_value', [
                                    'minutes' => app(ReadingTimeCalculator::class)->minutes((string) $get("body.{$locale}")),
                                ])),
                                TextInput::make("hero_caption.{$locale}")
                                    ->label(__('admin.articles.hero_caption'))
                                    ->maxLength(200),
                            ]),
                        ]),
                    Section::make(__('admin.common.image'))->schema([
                        SpatieMediaLibraryFileUpload::make('hero')
                            ->hiddenLabel()
                            ->collection(Article::HERO_COLLECTION)
                            ->image()
                            ->maxSize(8192),
                        SpatieMediaLibraryFileUpload::make('attachment')
                            ->label(__('admin.articles.attachment'))
                            ->collection(Article::ATTACHMENT_COLLECTION)
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480),
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
                        Toggle::make('is_original')->label(__('admin.common.original')),
                    ]),
                    Section::make(__('admin.common.details'))->schema([
                        Select::make('section_id')
                            ->label(__('admin.articles.section'))
                            ->options(fn () => SectionModel::query()->where('has_articles', true)->ordered()->get()->mapWithKeys(fn (SectionModel $section) => [$section->id => $section->name]))
                            ->required(),
                        Select::make('author_id')
                            ->label(__('admin.articles.author'))
                            ->options(fn () => Author::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
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
