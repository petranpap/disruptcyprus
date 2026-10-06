<?php

namespace App\Services\Content;

use App\Models\Event;

/**
 * Minimal RFC 5545 calendar file for one event (UTC times, escaped and folded lines).
 */
class IcsBuilder
{
    private const LINE_LIMIT = 75;

    public function build(Event $event, string $locale): string
    {
        $title = (string) $event->getTranslation('title', $locale);
        $description = trim(html_entity_decode(strip_tags((string) $event->getTranslation('description', $locale)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $location = $event->is_online ? (string) $event->online_url : implode(', ', array_filter([$event->location_name, $event->address, $event->city]));
        $endsAt = $event->ends_at ?? $event->starts_at->copy()->addHour();
        $url = $event->registration_url ?? rtrim((string) config('app.url'), '/').'/e/'.$event->slug;

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Disrupt Cyprus//Events//'.strtoupper($locale),
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$event->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$endsAt->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape($title),
            'DESCRIPTION:'.$this->escape($description),
            'LOCATION:'.$this->escape($location),
            'URL:'.$url,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(fn (string $line) => $this->fold($line), $lines))."\r\n";
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /**
     * Folds at 75 octets without splitting multi-byte (Greek) characters.
     */
    private function fold(string $line): string
    {
        $folded = '';
        $current = '';

        foreach (mb_str_split($line) as $character) {
            $limit = $folded === '' ? self::LINE_LIMIT : self::LINE_LIMIT - 1;

            if (strlen($current.$character) > $limit) {
                $folded .= $current."\r\n ";
                $current = '';
            }

            $current .= $character;
        }

        return $folded.$current;
    }
}
