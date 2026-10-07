import { useTranslation } from 'react-i18next'
import { EmptyState } from '@/components/ui/EmptyState'
import type { IconName } from '@/components/ui/Icon'

/** Temporary screen for routes delivered in Phase 5/6, so navigation is complete today. */
export function PlaceholderPage({ titleKey, icon = 'auto_awesome' }: { titleKey: string; icon?: IconName }) {
  const { t } = useTranslation()

  return (
    <section aria-labelledby="page-title">
      <h1 id="page-title" className="sr-only">
        {t(titleKey)}
      </h1>
      <EmptyState icon={icon} title={t('placeholder.title')} body={t('placeholder.body')} />
    </section>
  )
}
