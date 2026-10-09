<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Support\Preview\PreviewAccess;
use App\Support\Site\SiteLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Pre-launch page: what Disrupt Cyprus is, plus the team sign-in that unlocks the site and the app.
 */
class ComingSoonController extends Controller
{
    public function show(Request $request, ?string $error = null, int $status = 200): Response
    {
        $locale = SiteLocale::fromRequest($request);
        app()->setLocale($locale);

        return response()->view('site.coming-soon', [
            'locale' => $locale,
            'error' => $error,
            'username' => $request->string('username')->toString(),
            'next' => $this->next($request),
            'loginUrl' => route('preview.login', $locale === SiteLocale::DEFAULT ? [] : ['lang' => $locale]),
            'switchUrl' => $request->fullUrlWithQuery(['lang' => SiteLocale::other($locale)]),
            'industries' => Industry::query()->active()->orderBy('sort_order')->get()
                ->map(fn (Industry $industry) => ['name' => $industry->getTranslation('name', $locale), 'color' => $industry->color])
                ->all(),
        ], $status);
    }

    public function login(Request $request): Response|RedirectResponse
    {
        $username = $request->string('username')->toString();
        $password = $request->string('password')->toString();

        if (! PreviewAccess::attempt($username, $password)) {
            app()->setLocale(SiteLocale::fromRequest($request));

            return $this->show($request, __('coming_soon.login.failed'), 422);
        }

        return redirect()->to($this->destination($request))->withCookie(PreviewAccess::cookie());
    }

    public function logout(): RedirectResponse
    {
        return redirect()->to('/')->withCookie(PreviewAccess::forget());
    }

    /**
     * Where to go after signing in: the app (default) or a page on this site the visitor originally asked for.
     */
    private function destination(Request $request): string
    {
        $next = $this->next($request);

        return $next === 'app' ? SiteLocale::appUrl('/') : url($next);
    }

    private function next(Request $request): string
    {
        $next = $request->input('next', $request->getRequestUri());

        if (! is_string($next) || ! preg_match('#^/(?![/\\\\])#', $next) || str_starts_with($next, '/preview')) {
            return 'app';
        }

        // The landing page itself (any language) isn't worth returning to: the app is the point.
        if (in_array(parse_url($next, PHP_URL_PATH), ['/', '/en'], true)) {
            return 'app';
        }

        return $next;
    }
}
