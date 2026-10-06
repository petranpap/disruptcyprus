<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ContentStatus;
use App\Filament\Support\ContentStatusOptions;
use App\Filament\Support\IndustryFields;
use App\Models\Article;
use App\Services\Content\HtmlSanitizer;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['section', 'author']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('hero')
                    ->label('')
                    ->collection(Article::HERO_COLLECTION)
                    ->conversion('thumb')
                    ->square()
                    ->imageSize(44),
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->wrap()
                    ->limit(80)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('search_text', 'like', '%'.$search.'%')),
                TextColumn::make('section.name')
                    ->label(__('admin.articles.section'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label(__('admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn (ContentStatus $state) => ContentStatusOptions::label($state))
                    ->color(fn (ContentStatus $state) => ContentStatusOptions::color($state)),
                TextColumn::make('available_locales')
                    ->label(__('admin.common.languages'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper($state)),
                IconColumn::make('is_featured')->label(__('admin.common.featured'))->boolean()->toggleable(),
                IconColumn::make('is_original')->label(__('admin.common.original'))->boolean()->toggleable(),
                TextColumn::make('published_at')->label(__('admin.common.published_at'))->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('view_count')->label(__('admin.common.views'))->numeric()->sortable()->toggleable(),
                TextColumn::make('author.name')->label(__('admin.articles.author'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.common.status'))->options(ContentStatusOptions::options()),
                SelectFilter::make('section')->label(__('admin.articles.section'))->relationship('section', 'slug'),
                SelectFilter::make('industry')
                    ->label(__('admin.common.industries'))
                    ->options(fn () => IndustryFields::options())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $inner, $industryId) => $inner->whereHas('industries', fn (Builder $industries) => $industries->whereKey($industryId)),
                    )),
                TernaryFilter::make('is_featured')->label(__('admin.common.featured')),
                TernaryFilter::make('is_original')->label(__('admin.common.original')),
            ])
            ->recordActions([
                self::previewAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('archive')
                        ->label(__('admin.status.archived'))
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(fn ($article) => $article->update(['status' => ContentStatus::Archived]))),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function previewAction(): Action
    {
        return Action::make('preview')
            ->label(__('admin.articles.preview'))
            ->icon(Heroicon::OutlinedEye)
            ->modalWidth('3xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('OK')
            ->modalContent(fn (Article $record) => view('filament.article-preview', [
                'article' => $record,
                'sanitizer' => app(HtmlSanitizer::class),
            ]));
    }
}
