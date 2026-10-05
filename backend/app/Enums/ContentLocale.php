<?php

namespace App\Enums;

enum ContentLocale: string
{
    case Greek = 'el';
    case English = 'en';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
