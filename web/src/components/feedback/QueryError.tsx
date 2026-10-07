import { useTranslation } from 'react-i18next'
import { NetworkError } from '@/api/errors'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'

/** Error state for failed queries, offline-aware, with a retry. */
export function QueryError({ error, onRetry }: { error: unknown; onRetry: () => void }) {
  const { t } = useTranslation()
  const offline = error instanceof NetworkError || !navigator.onLine

  return (
    <EmptyState
      icon={offline ? 'cloud_off' : 'error'}
      tone="error"
      title={offline ? t('offline.title') : t('errors.generic')}
      body={offline ? t('offline.body') : undefined}
      action={
        <Button variant="outline" onClick={onRetry}>
          {t('common.retry')}
        </Button>
      }
    />
  )
}
