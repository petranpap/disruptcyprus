<?php

namespace App\Filament\Widgets;

use App\Enums\DigestStatus;
use App\Filament\Resources\Digests\DigestResource;
use App\Models\Digest;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class DigestsDue extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.widgets.digests_due'))
            ->query(Digest::query()->where('status', DigestStatus::Draft)->withCount('items')->orderBy('period_start'))
            ->emptyStateHeading(__('admin.widgets.no_digests_due'))
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->label(__('admin.common.title')),
                TextColumn::make('period_start')->label(__('admin.digests.period'))->date('d M Y'),
                TextColumn::make('items_count')->label(__('admin.digests.items')),
            ])
            ->recordActions([
                Action::make('review')->label(__('admin.digests.label'))->url(fn (Digest $record) => DigestResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
