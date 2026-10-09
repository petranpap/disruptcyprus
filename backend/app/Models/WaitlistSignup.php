<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Someone who asked to hear when Disrupt Cyprus launches (coming-soon page).
 */
#[Fillable(['name', 'email', 'locale', 'consented_at'])]
class WaitlistSignup extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
        ];
    }
}
