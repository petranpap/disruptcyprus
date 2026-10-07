import { useTranslation } from 'react-i18next'
import { useSectionArticles, useTrending } from '@/api/content'
import { FeedLayout } from '@/components/content/FeedLayout'
import { IndustryChips } from '@/components/content/IndustryChips'
import { FeedSidebar } from '@/components/content/Sidebar'
import { EmptyState } from '@/components/ui/EmptyState'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { LoadMore } from '@/components/feedback/LoadMore'
import { PullToRefresh } from '@/components/feedback/PullToRefresh'
import { QueryError } from '@/components/feedback/QueryError'
import { LatestDigestView } from '@/features/digests/DigestView'
import { useListParam, useParam } from '@/hooks/useListParam'
import { FeedSkeleton } from './FeedSkeleton'
import { FeedStream } from './FeedStream'

export type ArticleSection = 'news' | 'startups' | 'research' | 'investors'

const NEWS_TABS = ['latest', 'daily', 'monthly'] as const
type NewsTab = (typeof NEWS_TABS)[number]

function SectionStream({ section, industries }: { section: ArticleSection; industries: string[] }) {
  const { t, i18n } = useTranslation()
  const feed = useSectionArticles(section, industries, i18n.language)
  const trending = useTrending(i18n.language)

  if (feed.isPending) return <FeedSkeleton />
  if (feed.isError) return <QueryError error={feed.error} onRetry={() => void feed.refetch()} />
  if (feed.items.length === 0)
    return <EmptyState icon="search" title={t('feed.emptyTitle')} body={t('feed.emptyBody')} />

  return (
    <PullToRefresh onRefresh={() => feed.refetch()}>
      <FeedStream items={feed.items} trending={industries.length === 0 ? trending.data : undefined} />
      <LoadMore
        hasNextPage={feed.hasNextPage}
        isFetchingNextPage={feed.isFetchingNextPage}
        fetchNextPage={feed.fetchNextPage}
      />
    </PullToRefresh>
  )
}

/** News, Startups, Research and Investors: industry chips, and on News the Latest / Daily / Monthly sub-tabs. */
export function SectionPage({ section }: { section: ArticleSection }) {
  const { t } = useTranslation()
  const [industries, setIndustries] = useListParam('industry')
  const [tab, setTab] = useParam<NewsTab>('tab', 'latest', NEWS_TABS)
  const showDigest = section === 'news' && tab !== 'latest'

  return (
    <FeedLayout sidebar={<FeedSidebar />}>
      <div className="space-y-space-md">
        <h1 className="hidden font-headline text-headline-hero text-on-surface lg:block">{t(`tabs.${section}`)}</h1>
        {section === 'news' && (
          <SegmentedControl<NewsTab>
            label={t('tabs.news')}
            value={tab}
            onChange={setTab}
            className="lg:max-w-sm"
            options={NEWS_TABS.map((value) => ({ value, label: t(`news.${value}`) }))}
          />
        )}
        {!showDigest && <IndustryChips value={industries} onChange={setIndustries} />}
        <div className="pt-space-sm">
          {showDigest ? (
            <LatestDigestView kind="news" cadence={tab === 'daily' ? 'daily' : 'monthly'} />
          ) : (
            <SectionStream section={section} industries={industries} />
          )}
        </div>
      </div>
    </FeedLayout>
  )
}
