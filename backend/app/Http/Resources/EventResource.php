<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Http\Request;

/**
 * Full event for the detail screen.
 *
 * @property Event $resource
 */
class EventResource extends EventCardResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource;

        return [
            ...parent::toArray($request),
            'description' => app(HtmlSanitizer::class)->sanitize($this->translated($request, 'description')),
            'address' => $event->address,
            'online_url' => $event->online_url,
            'registration_url' => $event->registration_url,
            'organizer_name' => $event->organizer_name,
            'available_locales' => $event->available_locales,
            'ics_url' => route('events.ics', $event->slug),
            'share_url' => rtrim((string) config('app.url'), '/').'/e/'.$event->slug,
        ];
    }
}
