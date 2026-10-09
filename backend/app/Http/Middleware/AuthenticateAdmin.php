<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Models\Contracts\FilamentUser;

/**
 * Filament's panel auth, except that a signed-in non-staff account (e.g. a reader who used the app in the same
 * browser) is signed out and sent to the admin login, instead of hitting a bare 403 with no way forward.
 */
class AuthenticateAdmin extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();
        $user = $guard->user();

        if ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        parent::authenticate($request, $guards);
    }
}
