<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\ContentStatus;
use App\Filament\Support\ContentStatusOptions;
use App\Models\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('hero')
                    ->label('')
                    ->collection(Event::HERO_COLLECTION)
                    ->conversion('thumb')
                    ->square()
                    ->imageSize(44),
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->wrap()
                    ->limit(80)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('search_text', 'like', '%'.$search.'%')),
                TextColumn::make('starts_at')->label(__('admin.events.starts_at'))->dateTime('D d M Y H:i')->sortable(),
                TextColumn::make('city')
                    ->label(__('admin.events.where'))
                    ->state(fn (Event $record) => $record->is_online ? 'Online' : $record->city),
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
            ])
            ->filters([
                SelectFilter::make('when')
                    ->label(__('admin.events.when'))
                    ->options(['upcoming' => __('admin.events.upcoming'), 'past' => __('admin.events.past')])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'upcoming' => $query->where('starts_at', '>=', now()),
                        'past' => $query->where('starts_at', '<', now()),
                        default => $query,
                    }),
                SelectFilter::make('status')->label(__('admin.common.status'))->options(ContentStatusOptions::options()),
                TernaryFilter::make('is_online')->label(__('admin.events.is_online')),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
