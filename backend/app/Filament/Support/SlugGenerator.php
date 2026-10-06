<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One slug per item (all languages share it): from the English title, else the transliterated Greek one,
 * made unique with a numeric suffix.
 */
final class SlugGenerator
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string|null>  $titles
     */
    public static function unique(string $modelClass, array $titles, ?string $requested = null, ?int $ignoreId = null): string
    {
        $source = $requested ?: ($titles['en'] ?? null) ?: Str::transliterate((string) ($titles['el'] ?? ''));
        $base = Str::slug((string) $source) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while ($modelClass::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
