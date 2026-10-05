<?php

namespace App\Http\Requests\Auth;

class IssueTokenRequest extends LoginRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'device_name' => ['required', 'string', 'max:120'],
        ];
    }
}
