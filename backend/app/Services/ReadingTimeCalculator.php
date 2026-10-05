<?php

namespace App\Services;

class ReadingTimeCalculator
{
    public const WORDS_PER_MINUTE = 200;

    public function minutes(?string $html): int
    {
        $text = trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($text === '') {
            return 0;
        }

        $words = preg_split('/\s+/u', $text, flags: PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / self::WORDS_PER_MINUTE));
    }
}
