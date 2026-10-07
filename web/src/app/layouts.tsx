import { useMemo } from 'react'
import { Link, Outlet } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useMe } from '@/api/auth'
import { AppColumn } from '@/components/layout/AppColumn'
import { AppHeader, type SectionTab } from '@/components/layout/AppHeader'
import { BottomNav } from '@/components/layout/BottomNav'
import { Icon } from '@/components/ui/Icon'
import { Logo } from '@/components/ui/Logo'
import { AppGate } from './guards'

/** Home, sections and the bottom-nav destinations. */
export function AppShell() {
  const { t } = useTranslation()
  const { data: user } = useMe()

  const tabs = useMemo<SectionTab[]>(
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

  return (
    <AppGate>
      <AppColumn>
        <AppHeader tabs={tabs} />
        <main id="main" className="px-margin pt-space-md pb-28">
          <Outlet />
        </main>
        <BottomNav />
      </AppColumn>
    </AppGate>
  )
}

/** Sign in / sign up / password screens: Welcome-style canvas with ambient brand light. */
export function AuthLayout() {
  const { t } = useTranslation()

  return (
    <AppColumn className="overflow-hidden">
      <div
        className="pointer-events-none absolute -top-24 -left-20 size-64 rounded-pill bg-brand-cyan/15 blur-3xl"
        aria-hidden="true"
      />
      <div
        className="pointer-events-none absolute top-1/3 -right-24 size-60 rounded-pill bg-brand-crimson/10 blur-3xl"
        aria-hidden="true"
      />
      <div className="relative z-10 flex min-h-dvh flex-col px-space-md pt-safe pb-space-lg">
        <div className="flex items-center justify-between py-space-sm">
          <Link
            to="/welcome"
            className="-ml-2 inline-flex size-tap items-center justify-center rounded-pill text-on-surface hover:bg-surface-container"
            aria-label={t('common.back')}
          >
            <Icon name="arrow_back" size={24} />
          </Link>
          <Logo size="sm" />
          <span className="size-tap" aria-hidden="true" />
        </div>
        <main id="main" className="flex flex-1 flex-col">
          <Outlet />
        </main>
      </div>
    </AppColumn>
  )
}

export function OnboardingLayout() {
  return (
    <AppColumn>
      <Outlet />
    </AppColumn>
  )
}
