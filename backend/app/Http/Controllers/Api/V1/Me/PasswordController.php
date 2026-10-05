<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill(['password' => $request->validated('password')])->save();

        // Sign out other API clients; a token-authenticated caller keeps its own token.
        $otherTokens = $user->tokens();

        if ($request->bearerToken() !== null) {
            $otherTokens->whereKeyNot($user->currentAccessToken()->getKey());
        }

        $otherTokens->delete();

        return response()->json(['message' => __('passwords.updated')]);
    }
}
