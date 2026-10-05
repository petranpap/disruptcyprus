<?php

namespace App\Http\Requests\Auth;

use App\Enums\ContentLocale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'consent' => ['accepted'],
            'locale' => ['sometimes', Rule::in(ContentLocale::values())],
            'content_locales' => ['sometimes', 'array', 'min:1'],
            'content_locales.*' => ['distinct', Rule::in(ContentLocale::values())],
            'timezone' => ['sometimes', 'timezone:all'],
        ];
    }
}
