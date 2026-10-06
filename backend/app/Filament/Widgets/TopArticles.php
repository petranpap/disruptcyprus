<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class TopArticles extends TableWidget
{
    public const DAYS = 7;

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        $recentViews = DB::table('content_view_stats')
            ->selectRaw('viewable_id, SUM(views) as recent_views')
            ->where('viewable_type', 'article')
            ->where('bucket_at', '>=', now()->subDays(self::DAYS))
            ->groupBy('viewable_id');

        return $table
            ->heading(__('admin.widgets.top_articles'))
            ->query(
                Article::query()
                    ->joinSub($recentViews, 'recent', 'recent.viewable_id', '=', 'articles.id')
                    ->select('articles.*', 'recent.recent_views')
                    ->orderByDesc('recent.recent_views')
                    ->limit(10)
            )
            ->paginated(false)
            ->recordUrl(fn (Article $record) => ArticleResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('title')->label(__('admin.common.title'))->limit(60),
                TextColumn::make('recent_views')->label(__('admin.common.views'))->numeric(),
            ]);
    }
}
