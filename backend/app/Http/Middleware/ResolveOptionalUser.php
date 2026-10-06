<?php

namespace App\Http\Middleware;

use App\Services\Content\BookmarkState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets public endpoints see the signed-in user (session cookie or bearer token) without requiring one,
 * so cards can include `is_bookmarked` and feeds can respect content languages.
 */
class ResolveOptionalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        // Bookmark lookups are per request; never reuse them across requests in a long-lived process.
        app()->forgetInstance(BookmarkState::class);

        return $next($request);
    }
}
