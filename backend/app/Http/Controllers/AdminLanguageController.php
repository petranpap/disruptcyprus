<?php

namespace App\Http\Controllers;

use App\Enums\ContentLocale;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * EN/ΕΛ switch in the admin user menu; stored on the account so it follows the editor.
 */
class AdminLanguageController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ContentLocale::values(), true), 404);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['locale' => $locale])->save();

        return redirect()->back(fallback: '/admin');
    }
}
