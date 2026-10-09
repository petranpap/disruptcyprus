<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\JoinWaitlistRequest;
use App\Models\Industry;
use App\Models\WaitlistSignup;
use App\Support\Site\SiteLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Pre-launch page: what Disrupt Cyprus is, and a waitlist form for the launch email.
 */
class ComingSoonController extends Controller
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function show(Request $request, array $errors = [], int $status = 200): Response
    {
        $locale = SiteLocale::fromRequest($request);
        app()->setLocale($locale);

        return response()->view('site.coming-soon', [
            'locale' => $locale,
            'fieldErrors' => $errors,
            'old' => ['name' => (string) $request->input('name', ''), 'email' => (string) $request->input('email', ''), 'consent' => $request->boolean('consent')],
            'joined' => $request->boolean('joined'),
            'joinUrl' => route('waitlist.join', $locale === SiteLocale::DEFAULT ? [] : ['lang' => $locale]),
            'switchUrl' => url('/').'?'.http_build_query(array_filter(['lang' => SiteLocale::other($locale) === SiteLocale::DEFAULT ? null : SiteLocale::other($locale)])),
            'industries' => Industry::query()->active()->orderBy('sort_order')->get()
                ->map(fn (Industry $industry) => ['name' => $industry->getTranslation('name', $locale), 'color' => $industry->color])
                ->all(),
        ], $status);
    }

    /**
     * Joining twice, or with an address that is already on the list, looks exactly like joining once:
     * the page never reveals whether an email is on the waitlist.
     */
    public function join(JoinWaitlistRequest $request): RedirectResponse
    {
        $locale = SiteLocale::fromRequest($request);

        if (blank($request->validated('website'))) {
            WaitlistSignup::query()->firstOrCreate(
                ['email' => $request->validated('email')],
                ['name' => $request->validated('name'), 'locale' => $locale, 'consented_at' => now()],
            );
        }

        $query = array_filter(['lang' => $locale === SiteLocale::DEFAULT ? null : $locale, 'joined' => 1]);

        return redirect()->to(url('/').'?'.http_build_query($query).'#waitlist');
    }
}
