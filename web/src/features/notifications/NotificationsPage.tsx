import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { useMe } from '@/api/auth'
import { useMarkAllNotificationsRead, useMarkNotificationRead, useNotifications } from '@/api/notifications'
import type { InboxNotification, NotificationType } from '@/api/schemas'
import { CompactCardSkeleton } from '@/components/content/CardSkeletons'
import { LoadMore } from '@/components/feedback/LoadMore'
import { QueryError } from '@/components/feedback/QueryError'
import { Button, ButtonLink } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'
import { Icon, type IconName } from '@/components/ui/Icon'
import { useLocale } from '@/hooks/useLocale'
import { formatRelative } from '@/lib/dates'
import { cn } from '@/lib/cn'
import { PushCard } from './PushCard'

const TYPE_ICONS: Record<NotificationType, IconName> = {
  digest_published: 'newspaper',
  featured_article: 'trending_up',
  event_reminder: 'calendar_month',
  campaign: 'campaign',
  data_export: 'download',
  general: 'notifications',
}

function isToday(iso: string, now = new Date()): boolean {
  return new Date(iso).toDateString() === now.toDateString()
}

function NotificationRow({ item }: { item: InboxNotification }) {
  const { locale } = useLocale()
  const navigate = useNavigate()
  const markRead = useMarkNotificationRead()
  const unread = item.read_at === null

  const open = () => {
    if (unread) markRead.mutate(item.id)
    // Data exports link to the API (a download); everything else is an in-app route.
    if (item.url?.startsWith('/') && !item.url.startsWith('/api/')) navigate(item.url)
    else if (item.url) window.location.assign(item.url)
  }

  return (
    <li>
      <button
        type="button"
        onClick={open}
        className={cn(
          'flex w-full gap-space-sm rounded-card px-space-sm py-space-sm text-left transition-colors hover:bg-surface-container-low focus-visible:outline-2 focus-visible:outline-brand-cyan',
          unread && 'bg-primary-fixed/40',
        )}
      >
        <span className="flex size-10 shrink-0 items-center justify-center rounded-pill bg-surface-container text-primary">
          <Icon name={TYPE_ICONS[item.type]} size={20} />
        </span>
        <span className="min-w-0 flex-1">
          <span className={cn('block text-label-lg text-on-surface', unread && 'font-bold')}>{item.title}</span>
          {item.body && <span className="mt-0.5 block text-body-sm text-on-surface-variant">{item.body}</span>}
          <span className="mt-1 block text-label-sm text-outline">
            <time dateTime={item.created_at}>{formatRelative(item.created_at, locale)}</time>
          </span>
        </span>
        {unread && <span className="mt-2 size-2.5 shrink-0 rounded-pill bg-brand-cyan" aria-hidden="true" />}
      </button>
    </li>
  )
}

function Inbox() {
  const { t } = useTranslation()
  const notifications = useNotifications()
  const markAll = useMarkAllNotificationsRead()

  if (notifications.isPending) {
    return (
      <div className="space-y-space-sm" role="status" aria-busy="true">
        <CompactCardSkeleton />
        <CompactCardSkeleton />
      </div>
    )
  }
  if (notifications.isError) {
    return <QueryError error={notifications.error} onRetry={() => void notifications.refetch()} />
  }
  if (notifications.items.length === 0) {
    return (
      <EmptyState
        icon="notifications"
        title={t('notifications.emptyTitle')}
        body={t('notifications.emptyBody')}
        action={
          <ButtonLink to="/settings/notifications" variant="outline">
            {t('notifications.manage')}
          </ButtonLink>
        }
      />
    )
  }

  const today = notifications.items.filter((item) => isToday(item.created_at))
  const earlier = notifications.items.filter((item) => !isToday(item.created_at))
  const hasUnread = notifications.items.some((item) => item.read_at === null)

  return (
    <div className="space-y-space-lg">
      <div className="flex items-center justify-end gap-2">
        <Button
          variant="ghost"
          block={false}
          className="min-h-10 py-2"
          disabled={!hasUnread}
          onClick={() => markAll.mutate()}
          icon={<Icon name="done_all" size={18} />}
        >
          {t('notifications.markAllRead')}
        </Button>
      </div>
      {[
        { key: 'today', label: t('notifications.today'), items: today },
        { key: 'earlier', label: t('notifications.earlier'), items: earlier },
      ]
        .filter((group) => group.items.length > 0)
        .map((group) => (
          <section key={group.key} aria-labelledby={`inbox-${group.key}`}>
            <h2 id={`inbox-${group.key}`} className="mb-space-xs text-label-md tracking-wider text-on-surface-variant">
              {group.label}
            </h2>
            <ul className="space-y-1">
              {group.items.map((item) => (
                <NotificationRow key={item.id} item={item} />
              ))}
            </ul>
          </section>
        ))}
      <LoadMore
        hasNextPage={notifications.hasNextPage}
        isFetchingNextPage={notifications.isFetchingNextPage}
        fetchNextPage={notifications.fetchNextPage}
        showEnd={false}
      />
    </div>
  )
}

/** /notifications — the in-app inbox. Guests see what they would get with an account. */
export function NotificationsPage() {
  const { t } = useTranslation()
  const { data: user } = useMe()

  return (
    <div className="mx-auto w-full max-w-narrow space-y-space-lg">
      <h1 className="font-headline text-headline-hero-mobile text-on-surface lg:text-headline-hero">
        {t('notifications.title')}
      </h1>
      {user ? (
        <>
          <PushCard variant="banner" />
          <Inbox />
        </>
      ) : (
        <EmptyState
          icon="notifications"
          title={t('notifications.guestTitle')}
          body={t('notifications.guestBody')}
          action={
            <div className="flex flex-col gap-3">
              <ButtonLink to="/sign-up">{t('welcome.createAccount')}</ButtonLink>
              <ButtonLink to="/sign-in" variant="outline">
                {t('welcome.signIn')}
              </ButtonLink>
            </div>
          }
        />
      )}
    </div>
  )
}
