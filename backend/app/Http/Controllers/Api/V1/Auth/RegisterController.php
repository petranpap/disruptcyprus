<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'password', 'locale', 'content_locales', 'timezone']),
            'consent_at' => now(),
            'consent_version' => config('app.consent_version'),
        ]);

        event(new Registered($user));

        // SPA requests carry a session: sign the new user in straight away.
        if ($request->hasSession()) {
            // A new account on this device stays signed in (same as the default "Remember me" on sign-in).
            Auth::guard('web')->login($user, remember: true);
            $request->session()->regenerate();
        }

        return (new UserResource($user->refresh()))->response()->setStatusCode(201);
    }
}
