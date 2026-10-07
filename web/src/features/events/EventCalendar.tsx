import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useEventCalendar } from '@/api/content'
import { EventRow } from '@/components/content/EventCards'
import { QueryError } from '@/components/feedback/QueryError'
import { EmptyState } from '@/components/ui/EmptyState'
import { IconButton } from '@/components/ui/IconButton'
import { Skeleton } from '@/components/ui/Skeleton'
import { usePatternParam } from '@/hooks/useListParam'
import { currentLocale } from '@/i18n'
import { cn } from '@/lib/cn'
import { formatDate } from '@/lib/dates'
import { currentMonth, monthGrid, shiftMonth, today } from './calendar'

const MONTH_PATTERN = /^\d{4}-\d{2}$/

/** Month grid with event dots; selecting a day lists its events below (all month when nothing is selected). */
export function EventCalendar() {
  const { t, i18n } = useTranslation()
  const locale = currentLocale()
  const [month, setMonthParam] = usePatternParam('month', currentMonth(), MONTH_PATTERN)
  const [selected, setSelected] = useState<string | null>(null)
  const calendar = useEventCalendar(month, i18n.language)

  const days = useMemo(() => new Map((calendar.data?.days ?? []).map((day) => [day.date, day.events])), [calendar.data])
  const weekdays = useMemo(
    () =>
      Array.from({ length: 7 }, (_, index) =>
        formatDate(new Date(Date.UTC(2026, 0, 5 + index)), locale, { weekday: 'narrow' }, 'UTC'),
      ),
    [locale],
  )
  const visible = selected
    ? (days.get(selected) ?? [])
    : [...days.values()].flat().filter((event, index, all) => all.findIndex((other) => other.id === event.id) === index)
  const todayKey = today()

  const go = (delta: number) => {
    setSelected(null)
    setMonthParam(shiftMonth(month, delta))
  }

  return (
    <div className="space-y-space-lg lg:grid lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-start lg:gap-8 lg:space-y-0">
      <section
        className="rounded-card border border-card-stroke bg-surface-container-lowest p-space-md"
        aria-label={t('events.calendar')}
      >
        <div className="mb-3 flex items-center justify-between">
          <IconButton icon="arrow_back" label={t('events.previousMonth')} size={20} onClick={() => go(-1)} />
          <h2 className="font-headline text-headline-md text-on-surface capitalize">
            {formatDate(`${month}-01T12:00:00Z`, locale, { month: 'long', year: 'numeric' }, 'UTC')}
          </h2>
          <IconButton icon="arrow_forward" label={t('events.nextMonth')} size={20} onClick={() => go(1)} />
        </div>
        <table className="w-full table-fixed text-center">
          <thead>
            <tr>
              {weekdays.map((day, index) => (
                <th key={index} scope="col" className="pb-2 text-label-sm font-semibold text-outline">
                  {day}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {monthGrid(month).map((week, weekIndex) => (
              <tr key={weekIndex}>
                {week.map((date, dayIndex) => {
                  if (!date) return <td key={dayIndex} />
                  const events = days.get(date) ?? []
                  const isSelected = selected === date

                  return (
                    <td key={date} className="p-0.5">
                      <button
                        type="button"
                        onClick={() => setSelected(isSelected ? null : date)}
                        aria-pressed={isSelected}
                        aria-label={`${formatDate(`${date}T12:00:00Z`, locale, { day: 'numeric', month: 'long' }, 'UTC')}${events.length ? `, ${t('events.count', { count: events.length })}` : ''}`}
                        className={cn(
                          'relative flex size-11 w-full flex-col items-center justify-center rounded-control text-label-lg transition-colors',
                          isSelected
                            ? 'bg-primary-action text-on-primary'
                            : date === todayKey
                              ? 'bg-primary-fixed text-on-primary-fixed-variant'
                              : 'text-on-surface hover:bg-surface-container-low',
                          events.length === 0 && !isSelected && 'text-on-surface-variant',
                        )}
                      >
                        {Number(date.slice(8))}
                        {events.length > 0 && (
                          <span className="mt-0.5 flex gap-0.5" aria-hidden="true">
                            {events.slice(0, 3).map((event) => (
                              <span
                                key={event.id}
                                className={cn(
                                  'size-1 rounded-pill',
                                  isSelected ? 'bg-white' : event.is_bookmarked ? 'bg-brand-crimson' : 'bg-brand-cyan',
                                )}
                              />
                            ))}
                          </span>
                        )}
                      </button>
                    </td>
                  )
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      <section aria-live="polite" className="space-y-space-sm">
        <h3 className="text-label-md tracking-wider text-outline uppercase">
          {selected
            ? t('events.selectedDay', {
                date: formatDate(
                  `${selected}T12:00:00Z`,
                  locale,
                  { weekday: 'long', day: 'numeric', month: 'long' },
                  'UTC',
                ),
              })
            : t('events.allMonth')}
        </h3>
        {calendar.isPending ? (
          <Skeleton className="h-24" />
        ) : calendar.isError ? (
          <QueryError error={calendar.error} onRetry={() => void calendar.refetch()} />
        ) : visible.length === 0 ? (
          <EmptyState icon="calendar_month" title={selected ? t('events.noEventsDay') : t('events.emptyTitle')} />
        ) : (
          visible.map((event) => <EventRow key={event.id} event={event} />)
        )}
      </section>
    </div>
  )
}
