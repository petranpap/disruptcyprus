<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * GDPR: deleted accounts are anonymized immediately (AccountDeletionService) and permanently removed here
 * after the grace period. Foreign keys cascade (bookmarks, follows, devices…) or are nulled (author links,
 * campaign creators), so content written by staff survives.
 */
#[Signature('users:purge-deleted')]
#[Description('Permanently delete accounts that were deleted more than 30 days ago')]
class PurgeDeletedUsers extends Command
{
    public const GRACE_DAYS = 30;

    public function handle(): int
    {
        $purged = 0;

        User::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(self::GRACE_DAYS))
            ->chunkById(200, function ($users) use (&$purged): void {
                foreach ($users as $user) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                    $user->notifications()->delete();
                    $user->forceDelete();
                    $purged++;
                }
            });

        $this->info("Purged {$purged} deleted account(s).");

        return self::SUCCESS;
    }
}
