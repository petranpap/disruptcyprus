<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('maintenance:prune')]
#[Description('Delete old hourly view buckets and expired GDPR export files')]
class PruneActivityData extends Command
{
    public const VIEW_STATS_DAYS = 30;

    public const EXPORT_HOURS = 48;

    public function handle(): int
    {
        $buckets = DB::table('content_view_stats')->where('bucket_at', '<', now()->subDays(self::VIEW_STATS_DAYS))->delete();

        $disk = Storage::disk('local');
        $cutoff = now()->subHours(self::EXPORT_HOURS)->getTimestamp();
        $exports = 0;

        foreach ($disk->allFiles('exports') as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $exports++;
            }
        }

        $this->info("Pruned {$buckets} view bucket(s) and {$exports} export file(s).");

        return self::SUCCESS;
    }
}
