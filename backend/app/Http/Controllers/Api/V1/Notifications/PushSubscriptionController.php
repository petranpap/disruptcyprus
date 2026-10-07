<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\DestroyPushSubscriptionRequest;
use App\Http\Requests\Notifications\StorePushSubscriptionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Web Push devices. A device belongs to whoever subscribed it last (shared browsers move to the new account).
 */
class PushSubscriptionController extends Controller
{
    /**
     * VAPID public key the browser needs to subscribe. Public: it is not a secret.
     */
    public function publicKey(): JsonResponse
    {
        return response()->json(['data' => ['public_key' => config('webpush.vapid.public_key')]]);
    }

    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $subscription = $user->updatePushSubscription(
            $request->validated('endpoint'),
            $request->validated('keys.p256dh'),
            $request->validated('keys.auth'),
            $request->validated('content_encoding', 'aes128gcm'),
        );

        $subscription->forceFill([
            'locale' => $user->locale,
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'last_seen_at' => now(),
        ])->save();

        return response()->json(['data' => ['subscribed' => true]], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(DestroyPushSubscriptionRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->deletePushSubscription($request->validated('endpoint'));

        return response()->noContent();
    }
}
