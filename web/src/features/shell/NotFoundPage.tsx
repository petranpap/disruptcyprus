import { useTranslation } from 'react-i18next'
import { AppColumn } from '@/components/layout/AppColumn'
import { ButtonLink } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'

export function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <AppColumn className="flex items-center">
      <main id="main" className="w-full">
        <EmptyState
          icon="explore"
          title={t('errors.notFoundTitle')}
          body={t('errors.notFoundBody')}
          action={<ButtonLink to="/">{t('errors.goHome')}</ButtonLink>}
        />
      </main>
    </AppColumn>
  )
}
