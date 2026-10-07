<?php

namespace App\Models;

use App\Enums\ContentLocale;
use App\Enums\IndustryGroup;
use Database\Factories\IndustryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

#[Fillable(['slug', 'name', 'group', 'color', 'sort_order', 'is_active'])]
class Industry extends Model implements HasMedia
{
    /** @use HasFactory<IndustryFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    public const CACHE_KEY = 'industries.active';

    /**
     * Cache key for the rendered API payload in one language. Only plain arrays are cached:
     * Laravel 13 refuses to unserialize objects from the cache (serializable_classes = false).
     */
    public static function cacheKey(string $locale): string
    {
        return self::CACHE_KEY.'.'.$locale;
    }

    public static function forgetCache(): void
    {
        foreach (ContentLocale::values() as $locale) {
            Cache::forget(self::cacheKey($locale));
        }
    }

    public const IMAGE_COLLECTION = 'image';

    /** @var list<string> */
    public array $translatable = ['name'];

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCache());
        static::deleted(fn () => self::forgetCache());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => IndustryGroup::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Article, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withPivot('is_primary');
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withPivot('is_primary');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('notify')->withTimestamps();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::IMAGE_COLLECTION)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('tile')->fit(Fit::Crop, 480, 320)->format('webp');
    }
}
