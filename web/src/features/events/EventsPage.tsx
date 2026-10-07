import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useEvents, type EventRange } from '@/api/content'
import type { EventCard } from '@/api/schemas'
import { EventRow } from '@/components/content/EventCards'
import { FeedLayout } from '@/components/content/FeedLayout'
import { IndustryChips } from '@/components/content/IndustryChips'
import { DailyBriefingSidebar, TrendingSidebar } from '@/components/content/Sidebar'
import { LoadMore } from '@/components/feedback/LoadMore'
import { PullToRefresh } from '@/components/feedback/PullToRefresh'
import { QueryError } from '@/components/feedback/QueryError'
import { CompactCardSkeleton } from '@/components/content/CardSkeletons'
import { Chip } from '@/components/ui/Chip'
import { EmptyState } from '@/components/ui/EmptyState'
import { Icon } from '@/components/ui/Icon'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { useListParam, useParam } from '@/hooks/useListParam'
import { currentLocale } from '@/i18n'
import { formatDate } from '@/lib/dates'
import { upper } from '@/lib/greek'
import { addDays, eventDay, today } from './calendar'
import { EventCalendar } from './EventCalendar'

const RANGES = ['upcoming', 'week', 'month'] as const
const VIEWS = ['list', 'calendar'] as const

function groupByDay(events: EventCard[]): [string, EventCard[]][] {
  const groups = new Map<string, EventCard[]>()
  for (const event of events) {
    const day = eventDay(event.starts_at, event.timezone)
    groups.set(day, [...(groups.get(day) ?? []), event])
  }

  return [...groups.entries()]
}

function DayLabel({ day }: { day: string }) {
  const { t } = useTranslation()
  const now = today()
  const tomorrow = addDays(now, 1)
  const label =
    day === now
      ? t('events.today')
      : day === tomorrow
        ? t('events.tomorrow')
        : formatDate(`${day}T12:00:00Z`, currentLocale(), { weekday: 'long', day: 'numeric', month: 'long' }, 'UTC')

  return (
    <h2 className="sticky top-[calc(env(safe-area-inset-top)+92px)] z-10 bg-background/95 py-2 text-label-md tracking-wider text-on-surface-variant backdrop-blur-sm lg:top-16">
      {upper(label)}
    </h2>
  )
}

function EventList({ range, industries, online }: { range: EventRange; industries: string[]; online: boolean }) {
  const { t, i18n } = useTranslation()
  const events = useEvents({ range, industries, online }, i18n.language)
  const digest = events.firstPage?.meta.digest as
    { slug: string; title: string; intro: string | null } | null | undefined

  if (events.isPending) {
    return (
      <div className="space-y-space-sm" role="status" aria-busy="true">
        <CompactCardSkeleton />
        <CompactCardSkeleton />
        <CompactCardSkeleton />
      </div>
    )
  }
  if (events.isError) return <QueryError error={events.error} onRetry={() => void events.refetch()} />

  return (
    <PullToRefresh onRefresh={() => events.refetch()}>
      {digest && (
        <Link
          to={`/digests/${digest.slug}`}
          className="mb-space-lg block press rounded-card bg-ink p-space-md text-white transition-transform"
        >
          <p className="text-label-sm font-bold tracking-wider text-brand-crimson">
            {upper(t(range === 'week' ? 'digest.label.events_weekly' : 'digest.label.events_monthly'))}
          </p>
          <p className="mt-1 font-headline text-headline-sm">{digest.title}</p>
          {digest.intro && <p className="mt-1 line-clamp-2 text-body-sm text-white/75">{digest.intro}</p>}
        </Link>
      )}
      {events.items.length === 0 ? (
        <EmptyState icon="calendar_month" title={t('events.emptyTitle')} body={t('events.emptyBody')} />
      ) : (
        <div className="space-y-space-md">
          {groupByDay(events.items).map(([day, dayEvents]) => (
            <section key={day} className="space-y-space-sm">
              <DayLabel day={day} />
              {dayEvents.map((event) => (
                <EventRow key={event.id} event={event} />
              ))}
            </section>
          ))}
        </div>
      )}
      <LoadMore
        hasNextPage={events.hasNextPage}
        isFetchingNextPage={events.isFetchingNextPage}
        fetchNextPage={events.fetchNextPage}
        showEnd={events.items.length > 0}
      />
    </PullToRefresh>
  )
}

/** /events — Upcoming, This week, This month (list or calendar). */
export function EventsPage() {
  const { t } = useTranslation()
  const [range, setRange] = useParam<EventRange>('range', 'upcoming', RANGES)
  const [view, setView] = useParam<(typeof VIEWS)[number]>('view', 'list', VIEWS)
  const [industries, setIndustries] = useListParam('industry')
  const [onlineParam, setOnline] = useParam<'0' | '1'>('online', '0', ['0', '1'])
  const online = onlineParam === '1'
  const showCalendar = range === 'month' && view === 'calendar'

  return (
    <FeedLayout
      sidebar={
        <>
          <DailyBriefingSidebar />
          <TrendingSidebar />
        </>
      }
    >
      <div className="space-y-space-md">
        <h1 className="hidden font-headline text-headline-hero text-on-surface lg:block">{t('tabs.events')}</h1>
        <SegmentedControl<EventRange>
          label={t('tabs.events')}
          value={range}
          onChange={setRange}
          className="lg:max-w-md"
          options={RANGES.map((value) => ({ value, label: t(`events.${value}`) }))}
        />
        {range === 'month' && (
          <div className="flex justify-end">
            <SegmentedControl
              label={t('events.view')}
              value={view}
              onChange={setView}
              className="w-60"
              options={[
                { value: 'list', label: t('events.list') },
                { value: 'calendar', label: t('events.calendar') },
              ]}
            />
          </div>
        )}
        {!showCalendar && (
          <div className="flex flex-col gap-2">
            <IndustryChips value={industries} onChange={setIndustries} />
            <div>
              <Chip selected={online} onClick={() => setOnline(online ? '0' : '1')}>
                <Icon name="language" size={16} />
                {t('events.onlineOnly')}
              </Chip>
            </div>
          </div>
        )}
        <div className="pt-space-sm">
          {showCalendar ? <EventCalendar /> : <EventList range={range} industries={industries} online={online} />}
        </div>
      </div>
    </FeedLayout>
  )
}
