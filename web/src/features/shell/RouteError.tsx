import { useTranslation } from 'react-i18next'
import { Screen } from '@/components/layout/Screen'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'

/** Router error boundary: a failed lazy chunk (often after a deploy) or an unexpected render error. */
export function RouteError() {
  const { t } = useTranslation()

  return (
    <Screen className="flex items-center">
      <main id="main" className="w-full">
        <EmptyState
          icon="error"
          tone="error"
          title={t('errors.generic')}
          action={<Button onClick={() => window.location.reload()}>{t('common.retry')}</Button>}
        />
      </main>
    </Screen>
  )
}
