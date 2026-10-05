<?php

namespace App\Http\Requests\Me;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            // Social-only accounts can set a first password without one.
            'current_password' => [Rule::requiredIf($user->hasPassword()), 'nullable', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
