<?php

namespace App\Enums;

enum UserRole: string
{
    case Reader = 'reader';
    case Editor = 'editor';
    case Admin = 'admin';

    public function canAccessAdmin(): bool
    {
        return $this !== self::Reader;
    }
}
