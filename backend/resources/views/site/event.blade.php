@extends('site.layout')

@php
    $start = $event->starts_at->copy()->setTimezone($event->timezone)->locale($locale);
    $end = $event->ends_at?->copy()->setTimezone($event->timezone)->locale($locale);
    $timeFormat = $locale === 'el' ? 'HH:mm' : 'h:mm A';
@endphp

@section('content')
    <article class="reading">
        <header class="container-reading article-header event-header">
            @include('site.partials.date', ['event' => $event])
            <div>
                @if ($industry)
                    <p class="kickers"><span class="chip" style="--industry: {{ $industry->color }}">{{ $industry->getTranslation('name', $locale) }}</span></p>
                @endif
                <h1 class="article-title">{{ $title }}</h1>
            </div>
        </header>

        @if ($image)
            <figure class="container-wide hero-figure">
                <img src="{{ $image }}" alt="" width="1600" height="900" fetchpriority="high">
            </figure>
        @endif

        <div class="container-reading">
            <dl class="facts">
                <div>
                    <dt>{{ __('site.event.when') }}</dt>
                    <dd>
                        <time datetime="{{ $start->toIso8601String() }}">{{ $start->isoFormat('dddd LL') }}, {{ $start->isoFormat($timeFormat) }}</time>
                        @if ($end)
                            – <time datetime="{{ $end->toIso8601String() }}">{{ $end->isSameDay($start) ? $end->isoFormat($timeFormat) : $end->isoFormat('dddd LL, '.$timeFormat) }}</time>
                        @endif
                        <span class="meta">{{ $event->timezone === 'Asia/Nicosia' ? __('site.event.cyprus_time') : $event->timezone }}</span>
                    </dd>
                </div>
                <div>
                    <dt>{{ __('site.event.where') }}</dt>
                    <dd>
                        @if ($event->is_online)
                            {{ __('site.event.online') }}
                        @else
                            {{ $venue }}
                        @endif
                    </dd>
                </div>
                @if ($price)
                    <div><dt>{{ __('site.event.price') }}</dt><dd>{{ $price }}</dd></div>
                @endif
                @if ($event->organizer_name)
                    <div><dt>{{ __('site.event.organizer') }}</dt><dd>{{ $event->organizer_name }}</dd></div>
                @endif
            </dl>

            <div class="event-actions">
                @if ($event->registration_url)
                    <a href="{{ $event->registration_url }}" class="btn btn-primary" rel="noopener" target="_blank">{{ __('site.event.register') }}</a>
                @endif
                <a href="{{ url('/api/v1/events/'.$event->slug.'/ics') }}" class="btn btn-outline">{{ __('site.event.add_calendar') }}</a>
            </div>
        </div>

        <div class="container-reading prose" lang="{{ $locale }}">
            {!! $descriptionHtml !!}
        </div>

        <div class="container-reading">
            @include('site.partials.app-cta', [
                'title' => __('site.event.open'),
                'url' => $appUrl,
                'action' => __('site.article.open'),
            ])
        </div>
    </article>
@endsection
