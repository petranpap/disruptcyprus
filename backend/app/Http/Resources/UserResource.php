<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated user's own account.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarUrl = $this->getFirstMediaUrl(User::AVATAR_COLLECTION, 'thumb');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'avatar_url' => $avatarUrl !== '' ? $avatarUrl : null,
            'role' => $this->role->value,
            'locale' => $this->locale,
            'content_locales' => $this->content_locales,
            'timezone' => $this->timezone,
            'has_password' => $this->hasPassword(),
            'needs_consent' => $this->consent_at === null,
            'onboarded' => $this->onboarded_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
