<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\EventCalendarRequest;
use App\Http\Requests\Content\IndexEventsRequest;
use App\Http\Resources\EventCardResource;
use App\Http\Resources\EventResource;
use App\Models\Digest;
use App\Models\Event;
use App\Models\User;
use App\Services\Content\BookmarkState;
use App\Services\Content\EventRange;
use App\Services\Content\IcsBuilder;
use App\Services\Feed\ContentQueries;
use App\Support\ContentLocales;
use App\Support\DigestPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EventController extends Controller
{
    public const PER_PAGE = 20;

    public const MAX_CALENDAR_SPAN_DAYS = 14;

    public function index(IndexEventsRequest $request): AnonymousResourceCollection
    {
        $range = $request->range();
        $query = ContentQueries::inIndustries(ContentQueries::events(ContentLocales::accepted($request)), $request->industrySlugs())
            ->when($request->filled('city'), fn (Builder $events) => $events->where('city', $request->string('city')->toString()))
            ->when($request->has('online'), fn (Builder $events) => $events->where('is_online', $request->boolean('online')));

        $window = $range->window();

        if ($window === null) {
            $query->upcoming();
        } else {
            $this->overlapping($query, ...$window);
        }

        $events = $query->orderBy('starts_at')->orderBy('id')->cursorPaginate(self::PER_PAGE, cursor: $request->cursor());

        $user = $request->user();
        app(BookmarkState::class)->prime($user instanceof User ? $user : null, $events->items());

        return EventCardResource::collection($events)->additional(['meta' => ['digest' => $this->editorialDigest($range)]]);
    }

    /**
     * Month grid: every day an event touches (multi-day events appear on each day, capped).
     */
    public function calendar(EventCalendarRequest $request): JsonResponse
    {
        $monthStart = $request->monthStart();
        $monthEnd = $monthStart->addMonth();
        $timezone = DigestPeriod::timezone();

        $query = ContentQueries::events(ContentLocales::accepted($request));
        $events = $this->overlapping($query, $monthStart->utc(), $monthEnd->utc())->orderBy('starts_at')->get();

        $user = $request->user();
        app(BookmarkState::class)->prime($user instanceof User ? $user : null, $events);

        $days = [];

        foreach ($events as $event) {
            $first = CarbonImmutable::instance($event->starts_at)->setTimezone($timezone)->startOfDay();
            $last = CarbonImmutable::instance($event->ends_at ?? $event->starts_at)->setTimezone($timezone)->startOfDay();
            $last = $last->min($first->addDays(self::MAX_CALENDAR_SPAN_DAYS - 1));

            for ($day = $first; $day->lessThanOrEqualTo($last); $day = $day->addDay()) {
                if ($day->greaterThanOrEqualTo($monthStart) && $day->lessThan($monthEnd)) {
                    $days[$day->toDateString()][] = (new EventCardResource($event))->toArray($request);
                }
            }
        }

        ksort($days);

        return response()->json(['data' => [
            'month' => $monthStart->format('Y-m'),
            'timezone' => $timezone,
            'days' => array_map(fn (string $date, array $dayEvents) => ['date' => $date, 'events' => $dayEvents], array_keys($days), $days),
        ]]);
    }

    public function show(string $slug): EventResource
    {
        return new EventResource($this->publishedEvent($slug));
    }

    public function ics(Request $request, string $slug, IcsBuilder $ics): Response
    {
        $event = $this->publishedEvent($slug);
        $order = ContentLocales::preferenceOrder($request);
        $locale = $event->resolveLocale($order[0], $order) ?? ($event->available_locales[0] ?? app()->getLocale());

        return response($ics->build($event, $locale), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->slug.'.ics"',
        ]);
    }

    private function publishedEvent(string $slug): Event
    {
        return Event::query()->published()->where('slug', $slug)->with(ContentQueries::EVENT_CARD_RELATIONS)->firstOrFail();
    }

    /**
     * Events that start inside [from, to) or are still running when the window opens.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    private function overlapping(Builder $query, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $query->where(fn (Builder $events) => $events
            ->where(fn (Builder $starts) => $starts->where('starts_at', '>=', $from)->where('starts_at', '<', $to))
            ->orWhere(fn (Builder $running) => $running->where('starts_at', '<', $from)->where('ends_at', '>=', $from)));
    }

    /**
     * The published Weekly/Monthly Events digest for the current period, shown above the tab.
     *
     * @return array{slug: string, title: string, intro: string|null}|null
     */
    private function editorialDigest(EventRange $range): ?array
    {
        $cadence = match ($range) {
            EventRange::Week => DigestCadence::Weekly,
            EventRange::Month => DigestCadence::Monthly,
            EventRange::Upcoming => null,
        };

        if ($cadence === null) {
            return null;
        }

        $period = DigestPeriod::for(DigestKind::Events, $cadence, CarbonImmutable::now());

        $digest = Digest::query()->published()
            ->where('kind', DigestKind::Events)
            ->where('cadence', $cadence)
            ->whereDate('period_start', $period->start->toDateString())
            ->first();

        return $digest === null ? null : ['slug' => $digest->slug, 'title' => $digest->title, 'intro' => $digest->intro];
    }
}
