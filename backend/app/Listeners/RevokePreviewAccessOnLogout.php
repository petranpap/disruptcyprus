<?php

namespace App\Listeners;

use App\Support\Preview\PreviewAccess;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cookie;

/**
 * Signing out of the admin also closes the pre-launch preview on that browser.
 */
class RevokePreviewAccessOnLogout
{
    public function handle(Logout $event): void
    {
        // Only the admin sign-out: signing out of the reader app must not lock staff out of the preview.
        if (request()->is('admin', 'admin/*')) {
            Cookie::queue(PreviewAccess::forget());
        }
    }
}
