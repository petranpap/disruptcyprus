<?php

namespace App\Support\Site;

/**
 * Everything the public site layout needs for <head>: title, description, canonical URL,
 * social preview, language alternates and structured data.
 */
final readonly class PageMeta
{
    /**
     * @param  array<string, string>  $alternates  locale => absolute URL (hreflang)
     * @param  array<string, mixed>|null  $jsonLd
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $locale,
        public ?string $image = null,
        public string $type = 'website',
        public array $alternates = [],
        public ?array $jsonLd = null,
        public ?string $publishedAt = null,
        public ?string $modifiedAt = null,
    ) {}

    public function imageUrl(): string
    {
        return $this->image ?? asset('site/og-default.png');
    }

    public function fullTitle(): string
    {
        $suffix = __('site.meta.title_suffix');

        return $this->title === $suffix ? $suffix : "{$this->title} · {$suffix}";
    }

    public function jsonLdScript(): ?string
    {
        if ($this->jsonLd === null) {
            return null;
        }

        // JSON_HEX_TAG keeps "</script>" inside editor text from closing the tag.
        return json_encode(
            ['@context' => 'https://schema.org', ...$this->jsonLd],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }
}
