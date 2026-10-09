<?php

namespace App\Policies;

use App\Models\User;

/**
 * Personal data: administrators can view, export and delete signups. Nobody creates or edits them in the admin.
 */
class WaitlistSignupPolicy extends AdminOnlyPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
    {
        return false;
    }
}
