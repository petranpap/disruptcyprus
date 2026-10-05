<?php

namespace App\Models\Concerns;

use App\Enums\ContentLocale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps the `available_locales` column in sync with translated content.
 * A locale is available only when every field in {@see requiredTranslatableFields()} has text for it.
 *
 * @mixin Model
 */
trait TracksLocaleAvailability
{
    /**
     * @return list<string>
     */
    abstract protected function requiredTranslatableFields(): array;

    public static function bootTracksLocaleAvailability(): void
    {
        static::saving(function (self $model): void {
            $model->setAttribute('available_locales', $model->computeAvailableLocales());
        });
    }

    /**
     * @return list<string>
     */
    public function computeAvailableLocales(): array
    {
        $available = [];

        foreach (ContentLocale::values() as $locale) {
            $complete = true;

            foreach ($this->requiredTranslatableFields() as $field) {
                $value = $this->getTranslation($field, $locale, false);

                if (trim(strip_tags((string) $value)) === '') {
                    $complete = false;
                    break;
                }
            }

            if ($complete) {
                $available[] = $locale;
            }
        }

        return $available;
    }

    public function isAvailableIn(string $locale): bool
    {
        return in_array($locale, $this->available_locales ?? [], true);
    }

    /**
     * Pick the best locale to show: the requested one if available, otherwise the first
     * available locale that the reader accepts. Null when nothing acceptable exists.
     *
     * @param  list<string>  $acceptedLocales
     */
    public function resolveLocale(string $requested, array $acceptedLocales): ?string
    {
        if ($this->isAvailableIn($requested)) {
            return $requested;
        }

        foreach ($acceptedLocales as $locale) {
            if ($this->isAvailableIn($locale)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * @param  Builder<static>  $query
     * @param  list<string>  $locales
     * @return Builder<static>
     */
    public function scopeAvailableInAny(Builder $query, array $locales): Builder
    {
        return $query->where(function (Builder $inner) use ($locales): void {
            foreach ($locales as $locale) {
                $inner->orWhereJsonContains('available_locales', $locale);
            }
        });
    }
}
