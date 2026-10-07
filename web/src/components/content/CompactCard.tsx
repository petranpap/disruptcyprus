import { Link } from 'react-router'
import type { ArticleCard } from '@/api/schemas'
import { upper } from '@/lib/greek'
import { ArticleMeta } from './ArticleMeta'
import { BookmarkButton } from './BookmarkButton'

interface CompactCardProps {
  article: ArticleCard
  onToggleBookmark?: (article: ArticleCard) => void
}

/** 80×80 thumbnail row with kicker, two-line title and meta. */
export function CompactCard({ article, onToggleBookmark }: CompactCardProps) {
  return (
    <article className="group flex items-center gap-3.5 rounded-card border border-card-stroke bg-surface-container-lowest p-3 transition-colors hover:border-card-stroke-hover">
      <Link
        to={`/articles/${article.slug}`}
        className="size-20 shrink-0 overflow-hidden rounded-control bg-surface-container"
        tabIndex={-1}
        aria-hidden="true"
      >
        {article.image && (
          <img
            src={article.image.thumb}
            alt=""
            loading="lazy"
            className="size-full object-cover transition-transform duration-300 motion-safe:group-hover:scale-105"
          />
        )}
      </Link>
      <div className="min-w-0 flex-1">
        {article.primary_industry && (
          <p className="text-label-sm font-semibold tracking-wider text-primary">
            {upper(article.primary_industry.name)}
          </p>
        )}
        <Link to={`/articles/${article.slug}`} lang={article.locale}>
          <h3 className="mt-0.5 line-clamp-2 font-headline text-headline-sm leading-snug text-on-surface transition-colors group-hover:text-primary">
            {article.title}
          </h3>
        </Link>
        <div className="mt-1 flex items-center justify-between">
          <ArticleMeta article={article} />
          <BookmarkButton saved={article.is_bookmarked} onToggle={() => onToggleBookmark?.(article)} size={18} />
        </div>
      </div>
    </article>
  )
}
