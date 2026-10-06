<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use App\Events\ContentPublished;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps status and published_at consistent however content is saved (admin, seeder, scheduler):
 *  - published without a date → published now
 *  - published with a future date → scheduled
 *  - scheduled whose date has passed → published
 *  - scheduled without a date → draft
 * Fires ContentPublished when an item becomes published.
 *
 * @mixin Model
 */
trait NormalizesPublication
{
    public static function bootNormalizesPublication(): void
    {
        static::saving(function (self $model): void {
            $status = $model->getAttribute('status');
            $publishedAt = $model->getAttribute('published_at');

            if ($status === ContentStatus::Published && $publishedAt === null) {
                $model->setAttribute('published_at', now());
            } elseif ($status === ContentStatus::Published && $publishedAt->isFuture()) {
                $model->setAttribute('status', ContentStatus::Scheduled);
            } elseif ($status === ContentStatus::Scheduled && $publishedAt === null) {
                $model->setAttribute('status', ContentStatus::Draft);
            } elseif ($status === ContentStatus::Scheduled && ! $publishedAt->isFuture()) {
                $model->setAttribute('status', ContentStatus::Published);
            }
        });

        static::saved(function (self $model): void {
            $becamePublished = $model->getAttribute('status') === ContentStatus::Published
                && ($model->wasRecentlyCreated || $model->wasChanged('status'));

            if ($becamePublished) {
                ContentPublished::dispatch($model);
            }
        });
    }
}
