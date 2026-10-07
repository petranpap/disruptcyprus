import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { Spinner } from '@/components/ui/Spinner'
import { useInfiniteScroll } from '@/hooks/useInfiniteScroll'

interface LoadMoreProps {
  hasNextPage: boolean
  isFetchingNextPage: boolean
  fetchNextPage: () => unknown
  showEnd?: boolean
}

/** Infinite scroll sentinel with a visible "Load more" fallback (keyboard and no-IntersectionObserver users). */
export function LoadMore({ hasNextPage, isFetchingNextPage, fetchNextPage, showEnd = true }: LoadMoreProps) {
  const { t } = useTranslation()
  const sentinel = useInfiniteScroll(() => void fetchNextPage(), hasNextPage && !isFetchingNextPage)

  if (!hasNextPage) {
    return showEnd ? <p className="py-space-lg text-center text-label-md text-outline">{t('feed.end')}</p> : null
  }

  return (
    <div ref={sentinel} className="flex justify-center py-space-lg">
      {isFetchingNextPage ? (
        <Spinner className="text-brand-cyan" />
      ) : (
        <Button variant="outline" block={false} onClick={() => void fetchNextPage()}>
          {t('feed.loadMore')}
        </Button>
      )}
    </div>
  )
}
