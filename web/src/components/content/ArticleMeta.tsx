import { useTranslation } from 'react-i18next'
import type { ArticleCard } from '@/api/schemas'
import { currentLocale } from '@/i18n'
import { formatRelative } from '@/lib/dates'

/** "Disrupt Original · 2h ago" or "Author · 2h ago" (design meta row). */
export function ArticleMeta({ article, showReadingTime = false }: { article: ArticleCard; showReadingTime?: boolean }) {
  const { t } = useTranslation()
  const locale = currentLocale()

  return (
    <p className="flex min-w-0 items-center gap-1.5 text-label-sm text-outline">
      {article.is_original ? (
        <span className="font-semibold text-secondary">{t('common.disruptOriginal')}</span>
      ) : (
        <span className="truncate">{article.author.name}</span>
      )}
      {article.published_at && (
        <>
          <span aria-hidden="true">·</span>
          <time dateTime={article.published_at} className="shrink-0">
            {formatRelative(article.published_at, locale)}
          </time>
        </>
      )}
      {showReadingTime && article.reading_time_minutes && (
        <>
          <span aria-hidden="true">·</span>
          <span className="shrink-0">{t('common.minRead', { count: article.reading_time_minutes })}</span>
        </>
      )}
    </p>
  )
}
