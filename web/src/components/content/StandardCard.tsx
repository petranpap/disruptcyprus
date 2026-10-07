import { Link } from 'react-router'
import type { ArticleCard } from '@/api/schemas'
import { Badge } from '@/components/ui/Badge'
import { ArticleMeta } from './ArticleMeta'
import { BookmarkButton } from './BookmarkButton'

interface StandardCardProps {
  article: ArticleCard
  onToggleBookmark?: (article: ArticleCard) => void
}

/** 16:10 image with an industry tag, two-line serif title, meta row. */
export function StandardCard({ article, onToggleBookmark }: StandardCardProps) {
  return (
    <article className="group flex flex-col justify-between rounded-card border border-card-stroke bg-surface-container-lowest p-3.5 shadow-card transition-colors hover:border-card-stroke-hover">
      <Link to={`/articles/${article.slug}`} className="space-y-3" lang={article.locale}>
        <div className="relative aspect-[16/10] w-full overflow-hidden rounded-control bg-surface-container">
          {article.image && (
            <img
              src={article.image.card}
              alt=""
              loading="lazy"
              className="size-full object-cover transition-transform duration-300 motion-safe:group-hover:scale-105"
            />
          )}
          {article.primary_industry && (
            <Badge variant="tag" className="absolute bottom-2 left-2">
              {article.primary_industry.name}
            </Badge>
          )}
        </div>
        <h3 className="line-clamp-2 font-headline text-headline-sm text-on-surface transition-colors group-hover:text-primary">
          {article.title}
        </h3>
      </Link>
      <div className="mt-3 flex items-center justify-between pt-1 hairline-t">
        <ArticleMeta article={article} />
        <BookmarkButton saved={article.is_bookmarked} onToggle={() => onToggleBookmark?.(article)} size={18} />
      </div>
    </article>
  )
}
