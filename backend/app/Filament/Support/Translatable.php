<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * EN/GR tabs for spatie-translatable fields. Fields are named `field.el` / `field.en`, which maps
 * directly onto the JSON translations (spatie serializes and accepts the per-locale array).
 */
final class Translatable
{
    /** Greek first: it is the default language of the product. */
    public const LOCALES = ['el', 'en'];

    /**
     * @param  Closure(string $locale): list<Component|Field>  $fields
     */
    public static function tabs(Closure $fields, string $name = 'translations'): Tabs
    {
        return Tabs::make($name)
            ->tabs(array_map(
                fn (string $locale) => Tab::make(__('admin.locales.'.$locale))->schema($fields($locale)),
                self::LOCALES,
            ))
            ->columnSpanFull();
    }

    public static function other(string $locale): string
    {
        return $locale === 'el' ? 'en' : 'el';
    }

    /**
     * Empty editor output ("<p></p>") is stored as null so the language counts as missing.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public static function clean(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            foreach (self::LOCALES as $locale) {
                $value = $data[$field][$locale] ?? null;

                if (is_string($value) && trim(strip_tags($value, '<img>')) === '') {
                    $data[$field][$locale] = null;
                }
            }
        }

        return $data;
    }
}
