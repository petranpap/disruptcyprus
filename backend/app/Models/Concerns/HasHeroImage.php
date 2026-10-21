<?php

namespace App\Models\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Hero image collection with the WebP renditions used by the cards and the reader.
 * Models call both register* methods from their medialibrary hooks.
 *
 * @mixin InteractsWithMedia
 */
trait HasHeroImage
{
    public const HERO_COLLECTION = 'hero';

    /**
     * Every collection names its disk explicitly: Filament's upload field would otherwise fall back to
     * FILESYSTEM_DISK (the private "local" disk) and the app could not load the image.
     */
    protected function registerHeroImageCollection(): void
    {
        $this->addMediaCollection(self::HERO_COLLECTION)
            ->useDisk((string) config('media-library.disk_name'))
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    protected function registerHeroImageConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections(self::HERO_COLLECTION)
            ->fit(Fit::Crop, 320, 320)
            ->format('webp');

        $this->addMediaConversion('card')
            ->performOnCollections(self::HERO_COLLECTION)
            ->fit(Fit::Crop, 800, 500)
            ->format('webp');

        $this->addMediaConversion('hero')
            ->performOnCollections(self::HERO_COLLECTION)
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp');
    }
}
