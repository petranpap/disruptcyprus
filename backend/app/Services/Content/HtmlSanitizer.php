<?php

namespace App\Services\Content;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Allow-list sanitizer for editor HTML. Applied on output: stored HTML is never trusted.
 */
class HtmlSanitizer
{
    private const MAX_INPUT_LENGTH = 1_000_000;

    private SymfonySanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('hr')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('sub')
            ->allowElement('sup')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('cite')
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->allowElement('code')
            ->allowElement('pre')
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th', ['colspan', 'rowspan'])
            ->allowElement('td', ['colspan', 'rowspan'])
            ->allowElement('a', ['href', 'title'])
            ->allowElement('img', ['src', 'alt', 'width', 'height'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withMaxInputLength(self::MAX_INPUT_LENGTH);

        $this->sanitizer = new SymfonySanitizer($config);
    }

    public function sanitize(?string $html): string
    {
        return $this->sanitizer->sanitize((string) $html);
    }
}
