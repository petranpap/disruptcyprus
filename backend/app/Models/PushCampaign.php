<?php

namespace App\Models;

use App\Enums\PushAudience;
use App\Enums\PushCampaignStatus;
use App\Models\Concerns\StoresUtcTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

#[Fillable(['title', 'body', 'url', 'audience', 'industry_ids', 'status', 'created_by', 'queued_at', 'sent_at', 'recipients_count', 'failures_count'])]
class PushCampaign extends Model
{
    use HasTranslations, StoresUtcTimestamps;

    /** @var list<string> */
    public array $translatable = ['title', 'body'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'audience' => 'all',
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => PushAudience::class,
            'status' => PushCampaignStatus::class,
            'industry_ids' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === PushCampaignStatus::Draft;
    }

    /**
     * Readers this campaign targets: everyone, or followers of the selected industries.
     *
     * @return Builder<User>
     */
    public function audienceQuery(): Builder
    {
        return User::query()
            ->when(
                $this->audience === PushAudience::Industries,
                fn (Builder $users) => $users->whereHas('industries', fn (Builder $industries) => $industries->whereIn('industries.id', $this->industry_ids ?? [])),
            );
    }
}
