import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { useMe } from '@/api/auth'
import type { SectionTab } from '@/components/layout/SiteHeader'

export function useSectionTabs(): SectionTab[] {
  const { t } = useTranslation()
  const { data: user } = useMe()

  return useMemo(
    () => [
      { to: '/', label: user ? t('tabs.forYou') : t('tabs.trending'), end: true },
      { to: '/news', label: t('tabs.news') },
      { to: '/startups', label: t('tabs.startups') },
      { to: '/research', label: t('tabs.research') },
      { to: '/investors', label: t('tabs.investors') },
      { to: '/events', label: t('tabs.events') },
    ],
    [t, user],
  )
}
