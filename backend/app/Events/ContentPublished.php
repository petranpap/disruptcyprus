<?php

namespace App\Events;

use App\Models\Article;
use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An article or event became publicly visible (immediately or when its scheduled time passed).
 * Phase 6 listens for featured articles to notify industry followers.
 */
class ContentPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Article|Event $content) {}
}
