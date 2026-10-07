import { useTranslation } from 'react-i18next'
import { useMe } from '@/api/auth'
import { useForYouFeed, useTrending } from '@/api/content'
import type { ContentCard } from '@/api/schemas'
import { FeedLayout } from '@/components/content/FeedLayout'
import { FeedSidebar } from '@/components/content/Sidebar'
import { EmptyState } from '@/components/ui/EmptyState'
import { LoadMore } from '@/components/feedback/LoadMore'
import { PullToRefresh } from '@/components/feedback/PullToRefresh'
import { QueryError } from '@/components/feedback/QueryError'
import { FeedSkeleton } from './FeedSkeleton'
import { FeedStream } from './FeedStream'
import { PersonalizeCard } from './PersonalizeCard'

function ForYou() {
  const { t, i18n } = useTranslation()
  const feed = useForYouFeed(i18n.language, true)
  const trending = useTrending(i18n.language)
  const fallback = feed.firstPage?.meta.fallback === 'trending'

  if (feed.isPending) return <FeedSkeleton />
  if (feed.isError) return <QueryError error={feed.error} onRetry={() => void feed.refetch()} />
  if (feed.items.length === 0)
    return <EmptyState icon="auto_awesome" title={t('feed.emptyTitle')} body={t('feed.emptyBody')} />

  return (
    <PullToRefresh onRefresh={() => Promise.all([feed.refetch(), trending.refetch()])}>
      <FeedStream
        items={feed.items}
        trending={trending.data}
        afterLead={fallback ? <PersonalizeCard signedIn /> : undefined}
      />
      <LoadMore
        hasNextPage={feed.hasNextPage}
        isFetchingNextPage={feed.isFetchingNextPage}
        fetchNextPage={feed.fetchNextPage}
      />
    </PullToRefresh>
  )
}

function TrendingHome() {
  const { i18n } = useTranslation()
  const trending = useTrending(i18n.language)

  if (trending.isPending) return <FeedSkeleton />
  if (trending.isError) return <QueryError error={trending.error} onRetry={() => void trending.refetch()} />

  const items: ContentCard[] = trending.data

  return (
    <PullToRefresh onRefresh={() => trending.refetch()}>
      <FeedStream items={items} afterLead={<PersonalizeCard signedIn={false} />} />
    </PullToRefresh>
  )
}

/** "/" — For you for signed-in readers, Trending for guests. */
export function HomePage() {
  const { t } = useTranslation()
  const { data: user } = useMe()

  return (
    <FeedLayout sidebar={<FeedSidebar />}>
      <h1 className="sr-only">{user ? t('tabs.forYou') : t('tabs.trending')}</h1>
      {user ? <ForYou /> : <TrendingHome />}
    </FeedLayout>
  )
}
