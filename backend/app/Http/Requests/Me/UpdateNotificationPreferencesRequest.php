<?php

namespace App\Http\Requests\Me;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            'digest_news_daily' => ['sometimes', 'boolean'],
            'digest_news_monthly' => ['sometimes', 'boolean'],
            'digest_events_weekly' => ['sometimes', 'boolean'],
            'digest_events_monthly' => ['sometimes', 'boolean'],
            'event_reminders' => ['sometimes', 'boolean'],
            'delivery_time' => ['sometimes', 'date_format:H:i'],
        ];
    }
}
