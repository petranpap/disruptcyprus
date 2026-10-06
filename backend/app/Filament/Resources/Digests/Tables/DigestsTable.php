<?php

namespace App\Filament\Resources\Digests\Tables;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Enums\DigestStatus;
use App\Filament\Resources\Digests\Schemas\DigestForm;
use App\Models\Digest;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DigestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->defaultSort('period_start', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('admin.common.title'))->wrap(),
                TextColumn::make('kind')->label(__('admin.digests.kind'))->badge()
                    ->formatStateUsing(fn (DigestKind $state) => __('admin.digests.kinds.'.$state->value))
                    ->color(fn (DigestKind $state) => $state === DigestKind::News ? 'info' : 'danger'),
                TextColumn::make('cadence')->label(__('admin.digests.cadence'))->badge()->color('gray')
                    ->formatStateUsing(fn (DigestCadence $state) => __('admin.digests.cadences.'.$state->value)),
                TextColumn::make('period_start')->label(__('admin.digests.period'))->date('d M Y')->sortable(),
                TextColumn::make('status')->label(__('admin.common.status'))->badge()
                    ->formatStateUsing(fn (DigestStatus $state) => __('admin.status.'.$state->value))
                    ->color(fn (DigestStatus $state) => $state === DigestStatus::Published ? 'success' : 'warning'),
                TextColumn::make('items_count')->label(__('admin.digests.items'))->numeric(),
                IconColumn::make('edited')->label(__('admin.digests.edited'))->boolean()
                    ->state(fn (Digest $record) => $record->edited_at !== null),
                TextColumn::make('published_at')->label(__('admin.common.published_at'))->dateTime('d M Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('kind')->label(__('admin.digests.kind'))->options(DigestForm::kindOptions()),
                SelectFilter::make('cadence')->label(__('admin.digests.cadence'))->options(DigestForm::cadenceOptions()),
                SelectFilter::make('status')->label(__('admin.common.status'))->options([
                    'draft' => __('admin.status.draft'),
                    'published' => __('admin.status.published'),
                ]),
            ])
            ->recordActions([EditAction::make()]);
    }
}
