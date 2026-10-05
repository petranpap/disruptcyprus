<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Avatar upload lives on its own POST endpoint because PHP does not parse multipart PATCH bodies.
 */
class AvatarController extends Controller
{
    public function store(UploadAvatarRequest $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        $user->addMediaFromRequest('avatar')
            ->usingFileName('avatar.'.$request->file('avatar')?->extension())
            ->toMediaCollection(User::AVATAR_COLLECTION);

        return new UserResource($user->refresh());
    }

    public function destroy(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        $user->clearMediaCollection(User::AVATAR_COLLECTION);

        return new UserResource($user->refresh());
    }
}
