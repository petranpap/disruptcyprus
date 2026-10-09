<?php

namespace App\Http\Requests\Site;

use App\Http\Controllers\Site\ComingSoonController;
use App\Support\Site\SiteLocale;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * The coming-soon page is stateless (no session to flash errors into), so invalid input re-renders the page
 * with the errors and the visitor's input instead of redirecting back.
 */
class JoinWaitlistRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'consent' => ['accepted'],
            // Honeypot: hidden from people, filled in by bots (handled in the controller, not as an error).
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Friendly, human messages instead of the generic validation sentences.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('coming_soon.waitlist.errors.name'),
            'name.max' => __('coming_soon.waitlist.errors.name'),
            'email.required' => __('coming_soon.waitlist.errors.email'),
            'email.email' => __('coming_soon.waitlist.errors.email'),
            'email.max' => __('coming_soon.waitlist.errors.email'),
            'consent.accepted' => __('coming_soon.waitlist.errors.consent'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Validation messages in the page's language.
        app()->setLocale(SiteLocale::fromRequest($this));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            app(ComingSoonController::class)->show($this, $validator->errors()->toArray(), 422),
        );
    }
}
