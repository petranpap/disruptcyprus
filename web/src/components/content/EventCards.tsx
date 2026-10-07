import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import type { EventCard } from '@/api/schemas'
import { Icon } from '@/components/ui/Icon'
import { useBookmark } from '@/hooks/useBookmark'
import { currentLocale } from '@/i18n'
import { cn } from '@/lib/cn'
import { eventWhere } from '@/lib/events'
import { formatDate } from '@/lib/dates'
import { upper } from '@/lib/greek'
import { BookmarkButton } from './BookmarkButton'

/** 56px date chip: month in crimson, day in serif. Shown in the event's own timezone. */
export function DateChip({ date, timeZone, className }: { date: string; timeZone: string; className?: string }) {
  const locale = currentLocale()

  return (
    <span
      className={cn(
        'flex size-14 shrink-0 flex-col items-center justify-center rounded-control bg-surface-container-low',
        className,
      )}
      aria-hidden="true"
    >
      <span className="text-label-sm font-bold tracking-wider text-secondary">
        {upper(formatDate(date, locale, { month: 'short' }, timeZone).replace('.', ''))}
      </span>
      <span className="font-headline text-headline-md leading-none text-on-surface">
        {formatDate(date, locale, { day: 'numeric' }, timeZone)}
      </span>
    </span>
  )
}

/** Event row for lists, feeds and calendar days. */
export function EventRow({ event }: { event: EventCard }) {
  const { t } = useTranslation()
  const bookmark = useBookmark()
  const locale = currentLocale()

  return (
    <article className="group flex items-center gap-3.5 rounded-card border border-card-stroke bg-surface-container-lowest p-3 transition-colors hover:border-card-stroke-hover">
      <DateChip date={event.starts_at} timeZone={event.timezone} />
      <div className="min-w-0 flex-1">
        {event.primary_industry && (
          <p className="text-label-sm font-semibold tracking-wider text-primary">
            {upper(event.primary_industry.name)}
          </p>
        )}
        <Link to={`/events/${event.slug}`} lang={event.locale}>
          <h3 className="mt-0.5 line-clamp-2 font-headline text-headline-sm leading-snug text-on-surface transition-colors group-hover:text-primary">
            {event.title}
          </h3>
        </Link>
        <div className="mt-1 flex items-center justify-between gap-2">
          <p className="flex min-w-0 items-center gap-1 text-label-sm text-outline">
            <Icon name="schedule" size={14} className="shrink-0" />
            <time dateTime={event.starts_at} className="shrink-0">
              {formatDate(
                event.starts_at,
                locale,
                { weekday: 'short', hour: '2-digit', minute: '2-digit' },
                event.timezone,
              )}
            </time>
            <span aria-hidden="true">·</span>
            <span className="truncate">{eventWhere(event, t('common.online'))}</span>
          </p>
          <BookmarkButton saved={event.is_bookmarked} onToggle={() => bookmark(event)} size={18} />
        </div>
      </div>
    </article>
  )
}
