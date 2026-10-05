<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IssueTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Personal access tokens for non-browser clients (future native app). The PWA does not use this.
 */
class TokenController extends Controller
{
    public function store(IssueTokenRequest $request): JsonResponse
    {
        $credentials = $request->credentials();
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! $user->hasPassword() || ! Hash::check($credentials['password'], (string) $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $token = $user->createToken((string) $request->validated('device_name'));

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    public function destroy(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        // Cookie-authenticated callers have no personal access token to revoke.
        if ($request->bearerToken() !== null) {
            $user->currentAccessToken()->delete();
        }

        return response()->noContent();
    }
}
