<?php

namespace App\Http\Requests\Content;

use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class EventCalendarRequest extends FormRequest
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
            'month' => ['sometimes', 'date_format:Y-m'],
        ];
    }

    /**
     * First day of the requested month in the business timezone (default: current month).
     */
    public function monthStart(): CarbonImmutable
    {
        $month = $this->validated('month');

        return is_string($month)
            ? CarbonImmutable::createFromFormat('!Y-m', $month, DigestPeriod::timezone())->startOfMonth()
            : CarbonImmutable::now(DigestPeriod::timezone())->startOfMonth();
    }
}
