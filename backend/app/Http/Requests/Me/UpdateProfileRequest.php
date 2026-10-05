<?php

namespace App\Http\Requests\Me;

use App\Enums\ContentLocale;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $emailChanges = $this->has('email') && $this->input('email') !== $user->email;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            // Changing the email of a password account requires the current password.
            'current_password' => [Rule::requiredIf($emailChanges && $user->hasPassword()), 'nullable', 'current_password'],
            'locale' => ['sometimes', Rule::in(ContentLocale::values())],
            'content_locales' => ['sometimes', 'array', 'min:1'],
            'content_locales.*' => ['distinct', Rule::in(ContentLocale::values())],
            'timezone' => ['sometimes', 'timezone:all'],
            'consent' => ['sometimes', 'accepted'],
            'onboarding_completed' => ['sometimes', 'boolean'],
        ];
    }
}
