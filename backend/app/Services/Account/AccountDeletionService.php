<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * GDPR account deletion: removes personal data immediately and keeps an anonymized,
 * soft-deleted row only so foreign keys (e.g. an author link) stay consistent.
 */
class AccountDeletionService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->socialAccounts()->delete();
            $user->bookmarks()->delete();
            $user->industries()->detach();
            $user->notificationPreference()->delete();
            $user->notifications()->delete();
            $user->clearMediaCollection(User::AVATAR_COLLECTION);

            $user->forceFill([
                'name' => 'Deleted user',
                'email' => sprintf('deleted-%d-%s@invalid.local', $user->id, bin2hex(random_bytes(4))),
                'password' => null,
                'remember_token' => null,
                'email_verified_at' => null,
                'consent_at' => null,
                'consent_version' => null,
            ])->save();

            $user->delete();
        });

        Storage::disk('local')->deleteDirectory(UserDataExporter::directoryFor($user));
    }
}
