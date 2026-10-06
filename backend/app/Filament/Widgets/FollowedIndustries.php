<?php

namespace App\Filament\Widgets;

use App\Models\Industry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class FollowedIndustries extends TableWidget
{
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.widgets.followed_industries'))
            ->query(Industry::query()->withCount('followers')->orderByDesc('followers_count')->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label(__('admin.common.name')),
                TextColumn::make('followers_count')->label(__('admin.widgets.followers'))->numeric(),
            ]);
    }
}
