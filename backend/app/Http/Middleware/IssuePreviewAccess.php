<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Preview\PreviewAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin panel: while the pre-launch gate is on, signed-in staff get (and keep) the access cookie that opens the
 * public site and the app, so editors can check their work exactly as readers will see it.
 */
class IssuePreviewAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (PreviewAccess::enabled() && $user instanceof User && $user->role->canAccessAdmin() && ! PreviewAccess::granted($request)) {
            Cookie::queue(PreviewAccess::cookieFor($user));
        }

        return $next($request);
    }
}
