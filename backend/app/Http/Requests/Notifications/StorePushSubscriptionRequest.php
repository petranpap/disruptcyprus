<?php

namespace App\Http\Requests\Notifications;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionRequest extends FormRequest
{
    /**
     * The server POSTs to the endpoint, so only real browser push services are accepted (no SSRF via arbitrary URLs).
     */
    public const PUSH_SERVICE_HOSTS = [
        'fcm.googleapis.com',
        'android.googleapis.com',
        'updates.push.services.mozilla.com',
        'push.services.mozilla.com',
        'notify.windows.com',
        'push.apple.com',
    ];

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
            'endpoint' => ['required', 'string', 'max:500', 'url:https', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ! self::isPushServiceUrl($value)) {
                    $fail(__('validation.url', ['attribute' => $attribute]));
                }
            }],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['sometimes', 'string', 'in:aesgcm,aes128gcm'],
        ];
    }

    public static function isPushServiceUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach (self::PUSH_SERVICE_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }
}
