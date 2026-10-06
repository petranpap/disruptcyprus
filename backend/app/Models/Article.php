<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasHeroImage;
use App\Models\Concerns\MaintainsSearchText;
use App\Models\Concerns\NormalizesPublication;
use App\Models\Concerns\StoresUtcTimestamps;
use App\Models\Concerns\TracksLocaleAvailability;
use App\Services\ReadingTimeCalculator;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

#[Fillable(['section_id', 'author_id', 'slug', 'title', 'excerpt', 'body', 'hero_caption', 'is_original', 'is_featured', 'status', 'published_at'])]
class Article extends Model implements HasMedia
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasHeroImage, HasTranslations, InteractsWithMedia, MaintainsSearchText, NormalizesPublication, StoresUtcTimestamps, TracksLocaleAvailability;

    public const ATTACHMENT_COLLECTION = 'attachment';

    /** @var list<string> */
    public array $translatable = ['title', 'excerpt', 'body', 'hero_caption'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'available_locales' => '[]',
        'reading_time_minutes' => '{}',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $article): void {
            $calculator = app(ReadingTimeCalculator::class);
            $minutes = [];

            foreach ($article->getTranslations('body') as $locale => $body) {
                $minutes[$locale] = $calculator->minutes($body);
            }

            $article->reading_time_minutes = array_filter($minutes);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_locales' => 'array',
            'reading_time_minutes' => 'array',
            'is_original' => 'boolean',
            'is_featured' => 'boolean',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected function searchableTranslatableFields(): array
    {
        return ['title', 'excerpt', 'body'];
    }

    protected function requiredTranslatableFields(): array
    {
        return ['title', 'body'];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
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
        return $this->status === ContentStatus::Published && $this->published_at !== null && $this->published_at->lessThanOrEqualTo(now());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)->where('published_at', '<=', now());
    }

    public function registerMediaCollections(): void
    {
        $this->registerHeroImageCollection();

        $this->addMediaCollection(self::ATTACHMENT_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerHeroImageConversions();
    }
}
