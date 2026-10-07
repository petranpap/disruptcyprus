import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMe } from '@/api/auth'
import { articleApiUrl, useBookmarks } from '@/api/content'
import type { CardType } from '@/api/cardCache'
import { SAVED_CACHE } from '@/api/offline'
import { CompactCardSkeleton } from '@/components/content/CardSkeletons'
import { CompactCard } from '@/components/content/CompactCard'
import { EventRow } from '@/components/content/EventCards'
import { LoadMore } from '@/components/feedback/LoadMore'
import { QueryError } from '@/components/feedback/QueryError'
import { ButtonLink } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'
import { Icon } from '@/components/ui/Icon'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { useParam } from '@/hooks/useListParam'

const TYPES = ['article', 'event'] as const

/** Which saved articles have an offline copy in the service-worker cache. */
function useOfflineSlugs(slugs: string[]): Set<string> {
  const [available, setAvailable] = useState<Set<string>>(new Set())
  const key = slugs.join('|')

  useEffect(() => {
    if (!('caches' in window) || slugs.length === 0) return
    let cancelled = false

    void caches.open(SAVED_CACHE).then(async (cache) => {
      const found = await Promise.all(
        slugs.map(async (slug) => ((await cache.match(articleApiUrl(slug), { ignoreVary: true })) ? slug : null)),
      )
      if (!cancelled) setAvailable(new Set(found.filter((slug): slug is string => slug !== null)))
    })

    return () => {
      cancelled = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps -- `key` captures the slug list
  }, [key])

  return available
}

function SavedList({ type }: { type: CardType }) {
  const { t, i18n } = useTranslation()
  const bookmarks = useBookmarks(type, i18n.language)
  const offline = useOfflineSlugs(type === 'article' ? bookmarks.items.map((item) => item.slug) : [])

  if (bookmarks.isPending) {
    return (
      <div className="space-y-space-sm" role="status" aria-busy="true">
        <CompactCardSkeleton />
        <CompactCardSkeleton />
      </div>
    )
  }
  if (bookmarks.isError) return <QueryError error={bookmarks.error} onRetry={() => void bookmarks.refetch()} />
  if (bookmarks.items.length === 0)
    return (
      <EmptyState
        icon="bookmark"
        title={t('saved.emptyTitle')}
        body={t('saved.emptyBody')}
        action={<ButtonLink to="/">{t('errors.goHome')}</ButtonLink>}
      />
    )

  return (
    <div className="space-y-space-sm">
      {bookmarks.items.map((item) =>
        item.type === 'article' ? (
          <div key={`a-${item.id}`} className="relative">
            <CompactCard article={item} />
            {offline.has(item.slug) && (
              <span className="absolute top-2 right-3 inline-flex items-center gap-1 text-label-sm text-success">
                <Icon name="check" size={14} />
                {t('saved.offlineReady')}
              </span>
            )}
          </div>
        ) : (
          <EventRow key={`e-${item.id}`} event={item} />
        ),
      )}
      <LoadMore
        hasNextPage={bookmarks.hasNextPage}
        isFetchingNextPage={bookmarks.isFetchingNextPage}
        fetchNextPage={bookmarks.fetchNextPage}
        showEnd={false}
      />
    </div>
  )
}

/** /saved — Articles / Events. Guests see why saving needs an account. */
export function SavedPage() {
  const { t } = useTranslation()
  const { data: user } = useMe()
  const [type, setType] = useParam<CardType>('type', 'article', TYPES)

  return (
    <div className="mx-auto w-full max-w-narrow space-y-space-lg">
      <h1 className="font-headline text-headline-hero-mobile text-on-surface lg:text-headline-hero">
        {t('saved.title')}
      </h1>
      {user ? (
        <>
          <SegmentedControl<CardType>
            label={t('saved.title')}
            value={type}
            onChange={setType}
            className="max-w-sm"
            options={[
              { value: 'article', label: t('saved.articles') },
              { value: 'event', label: t('saved.events') },
            ]}
          />
          <SavedList type={type} />
        </>
      ) : (
        <EmptyState
          icon="bookmark"
          title={t('saved.guestTitle')}
          body={t('saved.guestBody')}
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
