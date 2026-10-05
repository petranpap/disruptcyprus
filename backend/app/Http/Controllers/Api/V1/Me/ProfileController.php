<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\DeleteAccountRequest;
use App\Http\Requests\Me\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Account\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'email', 'locale', 'content_locales', 'timezone']));

        if ($request->boolean('consent') && $user->consent_at === null) {
            $user->consent_at = now();
            $user->consent_version = config('app.consent_version');
        }

        if ($request->has('onboarding_completed')) {
            $user->onboarded_at = $request->boolean('onboarding_completed') ? ($user->onboarded_at ?? now()) : null;
        }

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return new UserResource($user);
    }

    public function destroy(DeleteAccountRequest $request, AccountDeletionService $deletion): Response
    {
        /** @var User $user */
        $user = $request->user();

        $deletion->delete($user);

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }
}
