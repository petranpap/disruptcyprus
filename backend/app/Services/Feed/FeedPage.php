<?php

namespace App\Services\Feed;

use App\Models\Article;
use App\Models\Event;

final readonly class FeedPage
{
    /**
     * @param  list<Article|Event>  $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
        public ?string $fallback = null,
    ) {}
}
