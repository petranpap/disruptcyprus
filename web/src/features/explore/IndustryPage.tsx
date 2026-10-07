import { useParams } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useIndustryFeed } from '@/api/content'
import { FeedLayout } from '@/components/content/FeedLayout'
import { FeedSidebar } from '@/components/content/Sidebar'
import { LoadMore } from '@/components/feedback/LoadMore'
import { QueryError } from '@/components/feedback/QueryError'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'
import { Icon } from '@/components/ui/Icon'
import { FeedSkeleton } from '@/features/feed/FeedSkeleton'
import { FeedStream } from '@/features/feed/FeedStream'
import { useFollowIndustry } from '@/hooks/useFollowIndustry'
import { upper } from '@/lib/greek'

/** /explore/:industry — articles and upcoming events of one industry, with Follow. */
export function IndustryPage() {
  const { industry: slug = '' } = useParams()
  const { t, i18n } = useTranslation()
  const feed = useIndustryFeed(slug, i18n.language)
  const industry = feed.firstPage?.meta.industry as
    { id: number; name: string; color: string; group: string } | undefined
  const follow = useFollowIndustry(industry?.id)

  return (
    <FeedLayout sidebar={<FeedSidebar />}>
      {industry && (
        <header
          className="mb-space-lg flex items-end justify-between gap-4 rounded-card p-space-md text-white"
          style={{ backgroundImage: `linear-gradient(135deg, ${industry.color}, #0F172A)` }}
        >
          <div>
            <p className="text-label-sm tracking-wider text-white/80">
              {upper(t(`onboarding.industries.groups.${industry.group}`))}
            </p>
            <h1 className="font-headline text-headline-hero-mobile lg:text-headline-hero">{industry.name}</h1>
          </div>
          <Button
            block={false}
            variant={follow.following ? 'outline' : 'primary'}
            loading={follow.pending}
            onClick={follow.toggle}
            icon={<Icon name={follow.following ? 'check' : 'tune'} size={18} />}
            aria-pressed={follow.following}
          >
            {follow.following ? t('explore.following') : t('explore.follow')}
          </Button>
        </header>
      )}
      {feed.isPending ? (
        <FeedSkeleton />
      ) : feed.isError ? (
        <QueryError error={feed.error} onRetry={() => void feed.refetch()} />
      ) : feed.items.length === 0 ? (
        <EmptyState icon="search" title={t('feed.emptyTitle')} body={t('feed.emptyBody')} />
      ) : (
        <>
          <FeedStream items={feed.items} />
          <LoadMore
            hasNextPage={feed.hasNextPage}
            isFetchingNextPage={feed.isFetchingNextPage}
            fetchNextPage={feed.fetchNextPage}
          />
        </>
      )}
    </FeedLayout>
  )
}
