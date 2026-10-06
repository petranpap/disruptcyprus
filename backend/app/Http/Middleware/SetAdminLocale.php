<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin panel speaks the signed-in staff member's language (users.locale).
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $locale = $user instanceof User ? $user->locale : (string) config('app.locale');

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
