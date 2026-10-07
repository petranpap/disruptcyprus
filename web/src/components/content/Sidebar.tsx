import type { ReactNode } from 'react'
import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useEvents, useLatestDigest, useTrending } from '@/api/content'
import { Icon } from '@/components/ui/Icon'
import { Skeleton } from '@/components/ui/Skeleton'
import { currentLocale } from '@/i18n'
import { cn } from '@/lib/cn'
import { formatCompactNumber, formatDate } from '@/lib/dates'
import { upper } from '@/lib/greek'
import { DateChip } from './EventCards'

function SidebarBlock({
  title,
  icon,
  to,
  children,
}: {
  title: string
  icon: 'trending_up' | 'calendar_month' | 'auto_awesome'
  to?: string
  children: ReactNode
}) {
  const { t } = useTranslation()

  return (
    <section className="rounded-card border border-card-stroke bg-surface-container-lowest p-space-md">
      <div className="mb-3 flex items-center justify-between">
        <h2 className="flex items-center gap-2 font-headline text-headline-sm text-on-surface">
          <Icon name={icon} size={20} className="text-brand-crimson" />
          {title}
        </h2>
        {to && (
          <Link to={to} className="text-label-md text-primary hover:underline">
            {t('common.viewAll')}
          </Link>
        )}
      </div>
      {children}
    </section>
  )
}

export function TrendingSidebar() {
  const { t, i18n } = useTranslation()
  const { data, isPending } = useTrending(i18n.language)

  return (
    <SidebarBlock title={t('feed.trendingToday')} icon="trending_up">
      {isPending ? (
        <div className="space-y-3">
          {[0, 1, 2, 3].map((index) => (
            <Skeleton key={index} className="h-10" />
          ))}
        </div>
      ) : (
        <ol className="divide-y divide-hairline">
          {(data ?? []).slice(0, 5).map((article, index) => (
            <li key={article.id}>
              <Link to={`/articles/${article.slug}`} className="group flex gap-3 py-2.5" lang={article.locale}>
                <span
                  className={cn(
                    'w-7 shrink-0 text-xl font-black tracking-tighter italic',
                    index === 0 ? 'text-brand-crimson' : index === 1 ? 'text-brand-cyan' : 'text-outline',
                  )}
                >
                  #{index + 1}
                </span>
                <span className="min-w-0">
                  <span className="line-clamp-2 font-headline text-body-sm font-semibold text-on-surface group-hover:text-primary">
                    {article.title}
                  </span>
                  <span className="text-label-sm text-outline">
                    {t('common.reads', { formatted: formatCompactNumber(article.reads, currentLocale()) })}
                  </span>
                </span>
              </Link>
            </li>
          ))}
        </ol>
      )}
    </SidebarBlock>
  )
}

export function UpcomingEventsSidebar() {
  const { t, i18n } = useTranslation()
  const { items, isPending } = useEvents({ range: 'upcoming', industries: [] }, i18n.language)

  return (
    <SidebarBlock title={t('feed.upcomingEvents')} icon="calendar_month" to="/events">
      {isPending ? (
        <Skeleton className="h-32" />
      ) : (
        <ul className="space-y-3">
          {items.slice(0, 3).map((event) => (
            <li key={event.id}>
              <Link to={`/events/${event.slug}`} className="group flex items-center gap-3" lang={event.locale}>
                <DateChip date={event.starts_at} timeZone={event.timezone} className="size-12" />
                <span className="min-w-0">
                  <span className="line-clamp-2 text-label-lg text-on-surface group-hover:text-primary">
                    {event.title}
                  </span>
                  <span className="text-label-sm text-outline">
                    {formatDate(
                      event.starts_at,
                      currentLocale(),
                      { weekday: 'short', hour: '2-digit', minute: '2-digit' },
                      event.timezone,
                    )}
                  </span>
                </span>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </SidebarBlock>
  )
}

export function DailyBriefingSidebar() {
  const { t, i18n } = useTranslation()
  const { data } = useLatestDigest('news', 'daily', i18n.language)

  if (!data) return null

  return (
    <Link
      to={`/digests/${data.slug}`}
      className="block press rounded-card bg-ink p-space-md text-white transition-transform"
    >
      <p className="text-label-sm font-bold tracking-wider text-brand-crimson">{upper(t('digest.label.news_daily'))}</p>
      <p className="mt-1 font-headline text-headline-sm">{data.title}</p>
      <p className="mt-2 text-label-md text-white/70">{t('digest.itemsCount', { count: data.items.length })}</p>
    </Link>
  )
}

export function FeedSidebar() {
  return (
    <>
      <DailyBriefingSidebar />
      <TrendingSidebar />
      <UpcomingEventsSidebar />
    </>
  )
}
