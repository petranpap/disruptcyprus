import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import type { ArticleCard } from '@/api/schemas'
import { Badge } from '@/components/ui/Badge'
import { Icon } from '@/components/ui/Icon'
import { currentLocale } from '@/i18n'
import { cn } from '@/lib/cn'
import { formatCompactNumber } from '@/lib/dates'

/** Ranked carousel card: italic "#n" (1 crimson, 2 cyan, then muted), tag, title, read count. */
export function TrendingCard({ article, rank }: { article: ArticleCard; rank: number }) {
  const { t } = useTranslation()

  return (
    <Link
      to={`/articles/${article.slug}`}
      lang={article.locale}
      className="flex w-[260px] flex-none snap-start flex-col justify-between rounded-card border border-card-stroke bg-surface-container-lowest p-3.5 transition-colors hover:border-card-stroke-hover"
    >
      <div className="space-y-2">
        <div className="flex items-center justify-between">
          <span
            className={cn(
              'text-2xl font-black tracking-tighter italic',
              rank === 1 ? 'text-brand-crimson' : rank === 2 ? 'text-brand-cyan' : 'text-outline',
            )}
          >
            #{rank}
          </span>
          {article.primary_industry && <Badge variant="neutral">{article.primary_industry.name}</Badge>}
        </div>
        <h3 className="line-clamp-2 font-headline text-headline-sm text-on-surface">{article.title}</h3>
      </div>
      <div className="mt-4 flex items-center justify-between text-label-sm text-outline">
        <span>{t('common.reads', { formatted: formatCompactNumber(article.reads, currentLocale()) })}</span>
        <Icon name="insights" size={16} className="text-brand-cyan" />
      </div>
    </Link>
  )
}
