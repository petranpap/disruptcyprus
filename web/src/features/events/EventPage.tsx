import type { ReactNode } from 'react'
import { useParams } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useEvent } from '@/api/content'
import type { Event } from '@/api/schemas'
import { BookmarkButton } from '@/components/content/BookmarkButton'
import { BottomDock } from '@/components/layout/BottomDock'
import { DetailShell } from '@/components/layout/DetailShell'
import { QueryError } from '@/components/feedback/QueryError'
import { Badge } from '@/components/ui/Badge'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Icon, type IconName } from '@/components/ui/Icon'
import { IconButton } from '@/components/ui/IconButton'
import { Skeleton } from '@/components/ui/Skeleton'
import { ReaderToolbar } from '@/features/reader/ReaderToolbar'
import { useBookmark } from '@/hooks/useBookmark'
import { useShare } from '@/hooks/useShare'
import { currentLocale } from '@/i18n'
import { cn } from '@/lib/cn'
import { formatDate } from '@/lib/dates'

function InfoRow({ icon, label, children }: { icon: IconName; label: string; children: ReactNode }) {
  return (
    <div className="flex gap-3 py-3">
      <span className="flex size-9 shrink-0 items-center justify-center rounded-pill bg-primary-fixed text-primary">
        <Icon name={icon} size={18} />
      </span>
      <div className="min-w-0">
        <p className="text-label-sm tracking-wider text-outline uppercase">{label}</p>
        <div className="text-body-sm text-on-surface">{children}</div>
      </div>
    </div>
  )
}

function whenText(event: Event, locale: 'el' | 'en'): string {
  const day = formatDate(
    event.starts_at,
    locale,
    { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' },
    event.timezone,
  )
  const start = formatDate(event.starts_at, locale, { hour: '2-digit', minute: '2-digit' }, event.timezone)
  const end = event.ends_at
    ? formatDate(event.ends_at, locale, { hour: '2-digit', minute: '2-digit' }, event.timezone)
    : null

  return `${day}, ${start}${end ? ` – ${end}` : ''}`
}

/** Venue and address without repeating the venue when the address already starts with it. */
function venueLine(event: Event): string {
  if (event.location_name && event.address?.toLowerCase().includes(event.location_name.toLowerCase()))
    return event.address
  return [event.location_name, event.address].filter(Boolean).join(' · ')
}

/** Register (primary), calendar file, save, share. Bottom dock on phones; side card on desktop. */
function EventActions({ event, layout }: { event: Event; layout: 'dock' | 'card' }) {
  const { t } = useTranslation()
  const bookmark = useBookmark()
  const share = useShare()

  const secondary = (
    <>
      <a
        href={event.ics_url}
        download
        className={cn(buttonClasses('outline', layout === 'card'), layout === 'dock' && 'px-3')}
        aria-label={t('events.addToCalendar')}
      >
        <Icon name="calendar_month" size={20} />
        {layout === 'card' && <span>{t('events.addToCalendar')}</span>}
      </a>
      <BookmarkButton saved={event.is_bookmarked} onToggle={() => bookmark(event)} size={22} className="mr-0" />
      <IconButton
        icon="share"
        label={t('common.share')}
        onClick={() => void share({ title: event.title ?? '', url: event.share_url })}
      />
    </>
  )

  const register = event.registration_url ? (
    <a
      href={event.registration_url}
      target="_blank"
      rel="noopener noreferrer"
      className={cn(buttonClasses('primary', layout === 'card'), layout === 'dock' && 'flex-1')}
    >
      {t('events.register')}
      <Icon name="arrow_forward" size={18} />
    </a>
  ) : null

  if (layout === 'dock') {
    return (
      <div className="lg:hidden">
        <BottomDock>
          {register}
          <div className="flex items-center gap-1">{secondary}</div>
        </BottomDock>
      </div>
    )
  }

  return (
    <div className="space-y-3 rounded-card border border-card-stroke bg-surface-container-lowest p-space-md shadow-card">
      {register}
      <div className="flex items-center justify-between gap-2">{secondary}</div>
    </div>
  )
}

function EventContent({ event }: { event: Event }) {
  const { t, i18n } = useTranslation()
  const locale = currentLocale()
  const mapsUrl = event.address
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(event.address)}`
    : null

  return (
    <div className="mx-auto w-full max-w-content px-space-md pt-space-sm lg:grid lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-10 lg:px-8 lg:pt-space-lg">
      <article lang={event.locale} className="min-w-0">
        <div className="relative aspect-[16/10] w-full overflow-hidden rounded-t-control rounded-b-hero-bottom bg-surface-container-high shadow-card sm:aspect-[16/9]">
          {event.image && <img src={event.image.hero} alt="" className="size-full object-cover" fetchPriority="high" />}
        </div>
        <header className="mt-space-lg space-y-space-sm">
          <div className="flex flex-wrap gap-2">
            {event.primary_industry && <Badge>{event.primary_industry.name}</Badge>}
            {event.is_online && <Badge variant="cyan">{t('common.online')}</Badge>}
          </div>
          <h1 className="font-headline text-headline-hero-mobile text-balance text-on-surface sm:text-headline-hero">
            {event.title}
          </h1>
        </header>

        <section
          className="mt-space-md divide-y divide-hairline rounded-card border border-card-stroke bg-surface-container-lowest px-space-md"
          lang={i18n.language}
        >
          <InfoRow icon="schedule" label={t('events.when')}>
            <time dateTime={event.starts_at}>{whenText(event, locale)}</time>
            <p className="text-label-sm text-outline">{t('events.timezoneNote', { timezone: event.timezone })}</p>
          </InfoRow>
          <InfoRow icon={event.is_online ? 'language' : 'location_on'} label={t('events.where')}>
            {event.is_online ? (
              event.online_url ? (
                <a
                  href={event.online_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary underline underline-offset-2"
                >
                  {t('events.joinOnline')}
                </a>
              ) : (
                t('common.online')
              )
            ) : (
              <>
                <p>{venueLine(event)}</p>
                {mapsUrl && (
                  <a
                    href={mapsUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-label-md text-primary underline underline-offset-2"
                  >
                    {t('events.openMaps')}
                  </a>
                )}
              </>
            )}
          </InfoRow>
          {event.price_info && (
            <InfoRow icon="auto_awesome" label={t('events.price')}>
              {event.price_info}
            </InfoRow>
          )}
          {event.organizer_name && (
            <InfoRow icon="person" label={t('events.organizer')}>
              {event.organizer_name}
            </InfoRow>
          )}
        </section>

        <div
          className="reader-body no-drop-cap mt-space-lg text-body-lg leading-[1.75]"
          dangerouslySetInnerHTML={{ __html: event.description }}
        />
      </article>

      <aside className="hidden lg:block">
        <div className="sticky top-32">
          <EventActions event={event} layout="card" />
        </div>
      </aside>
      <EventActions event={event} layout="dock" />
    </div>
  )
}

/** /events/:slug */
export function EventPage() {
  const { slug = '' } = useParams()
  const { i18n } = useTranslation()
  const event = useEvent(slug, i18n.language)

  return (
    <DetailShell toolbar={<ReaderToolbar showProgress={false} />}>
      {event.isPending ? (
        <div className="mx-auto max-w-reading space-y-space-md px-space-md pt-space-sm" role="status" aria-busy="true">
          <Skeleton className="aspect-[16/10] w-full" />
          <Skeleton className="h-9 w-3/4" />
          <Skeleton className="h-40 w-full" />
        </div>
      ) : event.isError ? (
        <div className="mx-auto max-w-reading px-space-md">
          <QueryError error={event.error} onRetry={() => void event.refetch()} />
        </div>
      ) : (
        <EventContent event={event.data} />
      )}
    </DetailShell>
  )
}
