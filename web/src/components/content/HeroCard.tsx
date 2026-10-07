import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import type { ArticleCard } from '@/api/schemas'
import { Badge } from '@/components/ui/Badge'
import { ArticleMeta } from './ArticleMeta'
import { BookmarkButton } from './BookmarkButton'

interface HeroCardProps {
  article: ArticleCard
  onToggleBookmark?: (article: ArticleCard) => void
}

/** Lead story: 4:3 image (16:9 on wider screens), scrim, badge, serif title on the image. */
export function HeroCard({ article, onToggleBookmark }: HeroCardProps) {
  const { t } = useTranslation()

  return (
    <article className="group relative press overflow-hidden rounded-card bg-surface-container-lowest shadow-float">
      <Link to={`/articles/${article.slug}`} className="block" lang={article.locale}>
        <div className="relative aspect-[4/3] w-full overflow-hidden bg-surface-container sm:aspect-[16/9]">
          {article.image && (
            <img
              src={article.image.hero}
              alt=""
              className="size-full object-cover transition-transform duration-500 ease-out motion-safe:group-hover:scale-105"
              fetchPriority="high"
            />
          )}
          <div className="absolute inset-0 scrim-hero" aria-hidden="true" />
          <div className="absolute top-4 left-4">
            {article.is_original ? (
              <Badge variant="original">{t('common.disruptOriginal')}</Badge>
            ) : (
              article.primary_industry && <Badge variant="cyan">{article.primary_industry.name}</Badge>
            )}
          </div>
          <h2 className="absolute inset-x-0 bottom-0 p-space-md font-headline text-headline-hero-mobile text-white sm:text-headline-hero">
            {article.title}
          </h2>
        </div>
        <div className="space-y-2 px-space-md pt-space-sm">
          {article.excerpt && (
            <p className="line-clamp-2 font-body text-body-md text-on-surface-variant">{article.excerpt}</p>
          )}
        </div>
      </Link>
      <div className="mx-space-md flex items-center justify-between pt-1 pb-1 hairline-t">
        <ArticleMeta article={article} />
        <BookmarkButton saved={article.is_bookmarked} onToggle={() => onToggleBookmark?.(article)} />
      </div>
    </article>
  )
}
