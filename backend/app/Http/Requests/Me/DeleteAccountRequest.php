<?php

namespace App\Http\Requests\Me;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteAccountRequest extends FormRequest
{
    public const CONFIRMATION_WORD = 'DELETE';

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

        return $user->hasPassword()
            ? ['password' => ['required', 'string', 'current_password']]
            : ['confirmation' => ['required', Rule::in([self::CONFIRMATION_WORD])]];
    }
}
