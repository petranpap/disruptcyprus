@php($rowTime = $event->starts_at->copy()->setTimezone($event->timezone)->locale($locale))
<li>
    <a href="{{ \App\Support\Site\SiteLocale::url('/e/'.$event->slug, $locale) }}" class="event-row">
        @include('site.partials.date', ['event' => $event])
        <span>
            <span class="event-row-title">{{ $event->getTranslation('title', $locale, false) }}</span>
            <span class="meta">{{ $rowTime->isoFormat($locale === 'el' ? 'dddd HH:mm' : 'dddd h:mm A') }} · {{ $event->is_online ? __('site.event.online') : $event->city }}</span>
        </span>
    </a>
</li>
