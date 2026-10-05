<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationPreferenceResource;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    public function show(Request $request): NotificationPreferenceResource
    {
        /** @var User $user */
        $user = $request->user();

        return new NotificationPreferenceResource($user->preferences());
    }

    public function update(UpdateNotificationPreferencesRequest $request): NotificationPreferenceResource
    {
        /** @var User $user */
        $user = $request->user();

        $preferences = $user->preferences();
        $preferences->update($request->validated());

        return new NotificationPreferenceResource($preferences->refresh());
    }
}
