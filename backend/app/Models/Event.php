<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasHeroImage;
use App\Models\Concerns\MaintainsSearchText;
use App\Models\Concerns\StoresUtcTimestamps;
use App\Models\Concerns\TracksLocaleAvailability;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

#[Fillable(['slug', 'title', 'description', 'starts_at', 'ends_at', 'timezone', 'location_name', 'address', 'city', 'is_online', 'online_url', 'registration_url', 'organizer_name', 'price_info', 'is_featured', 'status', 'published_at'])]
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasHeroImage, HasTranslations, InteractsWithMedia, MaintainsSearchText, StoresUtcTimestamps, TracksLocaleAvailability;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'price_info'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'available_locales' => '[]',
        'timezone' => 'Asia/Nicosia',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_locales' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_online' => 'boolean',
            'is_featured' => 'boolean',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected function searchableTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * @return list<string|null>
     */
    protected function searchablePlainValues(): array
    {
        return [$this->location_name, $this->address, $this->city, $this->organizer_name];
    }

    protected function requiredTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * @return BelongsToMany<Industry, $this>
     */
    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class)->withPivot('is_primary');
    }

    /**
     * @return MorphMany<Bookmark, $this>
     */
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    /**
     * @return MorphMany<DigestItem, $this>
     */
    public function digestItems(): MorphMany
    {
        return $this->morphMany(DigestItem::class, 'itemable');
    }

    public function primaryIndustry(): ?Industry
    {
        return $this->industries->firstWhere('pivot.is_primary', true) ?? $this->industries->first();
    }

    /**
     * In-memory equivalent of the published() scope.
     */
    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published && ($this->published_at === null || $this->published_at->lessThanOrEqualTo(now()));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)
            ->where(fn (Builder $inner) => $inner->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(fn (Builder $inner) => $inner
            ->where('starts_at', '>=', now())
            ->orWhere('ends_at', '>=', now()));
    }

    public function registerMediaCollections(): void
    {
        $this->registerHeroImageCollection();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerHeroImageConversions();
    }
}
