<?php

namespace App\Listeners;

use App\Events\DigestPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Seam for digest notifications. Phase 6 replaces the body with in-app + Web Push delivery
 * to subscribers of this digest kind, respecting delivery time and timezone.
 */
class QueueDigestNotifications implements ShouldQueue
{
    public function handle(DigestPublished $event): void
    {
        Log::info('Digest published; notification delivery arrives in Phase 6.', [
            'digest' => $event->digest->slug,
        ]);
    }
}
