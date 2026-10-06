<?php

namespace App\Policies;

use App\Models\User;

/**
 * Editorial resources: editors and admins create and edit; only admins delete (editors archive).
 */
abstract class EditorialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function view(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function update(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
