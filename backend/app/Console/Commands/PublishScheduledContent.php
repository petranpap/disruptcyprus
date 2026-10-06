<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Event;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:publish-scheduled')]
#[Description('Publish articles and events whose scheduled time has passed')]
class PublishScheduledContent extends Command
{
    public function handle(): int
    {
        $published = 0;

        foreach ([Article::class, Event::class] as $modelClass) {
            $due = $modelClass::query()
                ->where('status', ContentStatus::Scheduled)
                ->where('published_at', '<=', now())
                ->get();

            // Saving each model lets NormalizesPublication flip the status and fire ContentPublished.
            foreach ($due as $item) {
                $item->save();
                $published++;
            }
        }

        $this->info("Published {$published} item(s).");

        return self::SUCCESS;
    }
}
