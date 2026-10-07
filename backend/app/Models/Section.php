<?php

namespace App\Models;

use App\Enums\ContentLocale;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

#[Fillable(['slug', 'name', 'has_articles', 'sort_order'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory, HasTranslations;

    public const CACHE_KEY = 'sections.all';

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
            'has_articles' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
