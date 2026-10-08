{{-- Event date chip, as in the app's event rows (CSS uppercase drops Greek accents when lang="el"). --}}
@php($local = $event->starts_at->copy()->setTimezone($event->timezone)->locale($locale))
<span class="date-chip" aria-hidden="true">
    <span class="date-chip-month">{{ $local->isoFormat('MMM') }}</span>
    <span class="date-chip-day">{{ $local->format('j') }}</span>
</span>
