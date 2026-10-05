<?php

namespace App\Http\Requests\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndustriesRequest extends FormRequest
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
        $activeIndustry = Rule::exists('industries', 'id')->where('is_active', true);

        return [
            'industry_ids' => ['present', 'array', 'max:33'],
            'industry_ids.*' => ['integer', 'distinct', $activeIndustry],
            'notify_ids' => ['sometimes', 'array'],
            // Notifications only for industries the user follows.
            'notify_ids.*' => ['integer', 'distinct', Rule::in($this->input('industry_ids', []))],
        ];
    }

    /**
     * @return array<int, array{notify: bool}>
     */
    public function syncPayload(): array
    {
        $notifyIds = array_map('intval', $this->validated('notify_ids', []));
        $payload = [];

        foreach ($this->validated('industry_ids') as $industryId) {
            $payload[(int) $industryId] = ['notify' => in_array((int) $industryId, $notifyIds, true)];
        }

        return $payload;
    }
}
