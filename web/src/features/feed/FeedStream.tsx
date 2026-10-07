import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import type { ArticleCard, ContentCard } from '@/api/schemas'
import { ContentCardView } from '@/components/content/ContentCardView'
import { SectionHeader } from '@/components/content/SectionHeader'
import { TrendingCard } from '@/components/content/TrendingCard'

/**
 * Editorial rhythm from the home design: hero, a pair of standard cards, (phones) the trending carousel,
 * then repeating blocks of two standard cards followed by four compact rows.
 */
export function FeedStream({
  items,
  trending,
  afterLead,
}: {
  items: ContentCard[]
  trending?: ArticleCard[]
  afterLead?: ReactNode
}) {
  const { t } = useTranslation()
  const [lead, ...rest] = items
  const pair = rest.slice(0, 2)
  const tail = rest.slice(2)

  const blocks: ContentCard[][] = []
  for (let index = 0; index < tail.length; index += 6) blocks.push(tail.slice(index, index + 6))

  return (
    <div className="space-y-space-xl">
      {lead && <ContentCardView item={lead} variant="hero" />}
      {afterLead}
      {pair.length > 0 && (
        <div className="grid gap-gutter sm:grid-cols-2">
          {pair.map((item) => (
            <ContentCardView key={`${item.type}-${item.id}`} item={item} variant="standard" />
          ))}
        </div>
      )}
      {trending && trending.length > 0 && (
        <section className="space-y-3 lg:hidden" aria-labelledby="trending-carousel">
          <div id="trending-carousel">
            <SectionHeader title={t('feed.trendingToday')} icon="trending_up" />
          </div>
          <div className="-mx-margin no-scrollbar flex snap-x gap-3.5 overflow-x-auto px-margin pb-2">
            {trending.map((article, index) => (
              <TrendingCard key={article.id} article={article} rank={index + 1} />
            ))}
          </div>
        </section>
      )}
      {blocks.map((block, blockIndex) => (
        <div key={blockIndex} className="space-y-space-md">
          {blockIndex === 0 && (
            <h2 className="pb-2 text-label-md tracking-wider text-outline uppercase hairline-b">{t('feed.latest')}</h2>
          )}
          <div className="grid gap-gutter sm:grid-cols-2">
            {block.slice(0, 2).map((item) => (
              <ContentCardView key={`${item.type}-${item.id}`} item={item} variant="standard" />
            ))}
          </div>
          <div className="space-y-space-sm">
            {block.slice(2).map((item) => (
              <ContentCardView key={`${item.type}-${item.id}`} item={item} variant="compact" />
            ))}
          </div>
        </div>
      ))}
    </div>
  )
}
