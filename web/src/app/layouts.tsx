import { Link, Outlet } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useMe } from '@/api/auth'
import { BottomNav } from '@/components/layout/BottomNav'
import { Container, Screen } from '@/components/layout/Screen'
import { SiteFooter } from '@/components/layout/SiteFooter'
import { SiteHeader } from '@/components/layout/SiteHeader'
import { useSectionTabs } from '@/hooks/useSectionTabs'
import { Icon, type IconName } from '@/components/ui/Icon'
import { Logo } from '@/components/ui/Logo'
import { AppGate } from './guards'

/**
 * Home, sections and account pages. Phones: app-style header with tab row + bottom navigation.
 * Desktop: website top bar, content centred up to 1200px, footer.
 */
export function AppShell() {
  const { data: user } = useMe()
  const tabs = useSectionTabs()

  return (
    <AppGate>
      <Screen className="flex flex-col">
        <SiteHeader tabs={tabs} user={user} />
        <main id="main" className="flex-1 pt-space-md pb-28 lg:pt-space-xl lg:pb-space-xl">
          <Container>
            <Outlet />
          </Container>
        </main>
        <SiteFooter tabs={tabs} />
        <BottomNav />
      </Screen>
    </AppGate>
  )
}

const BRAND_POINTS: { icon: IconName; title: string; body: string }[] = [
  { icon: 'tune', title: 'brand.personalizedTitle', body: 'brand.personalizedBody' },
  { icon: 'auto_awesome', title: 'brand.dailyTitle', body: 'brand.dailyBody' },
  { icon: 'calendar_month', title: 'brand.eventsTitle', body: 'brand.eventsBody' },
]

/** Desktop-only editorial panel beside the auth forms. */
export function BrandPanel() {
  const { t } = useTranslation()

  return (
    <aside className="relative hidden overflow-hidden bg-ink text-white lg:flex lg:flex-col lg:justify-between lg:p-12">
      <div
        className="pointer-events-none absolute -top-32 -left-24 size-96 rounded-pill bg-brand-cyan/30 blur-3xl"
        aria-hidden="true"
      />
      <div
        className="pointer-events-none absolute -right-24 bottom-0 size-96 rounded-pill bg-brand-crimson/25 blur-3xl"
        aria-hidden="true"
      />
      <Link to="/welcome" className="relative z-10 w-fit" aria-label={t('common.appName')}>
        <Logo size="md" />
      </Link>
      <div className="relative z-10 max-w-lg space-y-8">
        <p className="font-headline text-headline-hero">{t('welcome.headline')}</p>
        <ul className="space-y-5">
          {BRAND_POINTS.map((point) => (
            <li key={point.title} className="flex gap-4">
              <span className="flex size-10 shrink-0 items-center justify-center rounded-control bg-white/10 text-brand-cyan">
                <Icon name={point.icon} size={22} />
              </span>
              <span>
                <span className="block text-label-lg">{t(point.title)}</span>
                <span className="block text-body-sm text-white/70">{t(point.body)}</span>
              </span>
            </li>
          ))}
        </ul>
      </div>
      <p className="relative z-10 text-label-sm text-white/50">{t('welcome.tagline')}</p>
    </aside>
  )
}

/** Sign in / sign up / password screens: phone = Welcome-style canvas; desktop = brand panel + centred form. */
export function AuthLayout() {
  const { t } = useTranslation()

  return (
    <Screen className="overflow-hidden lg:grid lg:grid-cols-2">
      <BrandPanel />
      <div className="relative min-h-dvh">
        <div
          className="pointer-events-none absolute -top-24 -left-20 size-64 rounded-pill bg-brand-cyan/15 blur-3xl lg:hidden"
          aria-hidden="true"
        />
        <div
          className="pointer-events-none absolute top-1/3 -right-24 size-60 rounded-pill bg-brand-crimson/10 blur-3xl lg:hidden"
          aria-hidden="true"
        />
        <div className="relative z-10 mx-auto flex min-h-dvh w-full max-w-md flex-col px-space-md pt-safe pb-space-lg lg:justify-center lg:py-12">
          <div className="flex items-center justify-between py-space-sm lg:hidden">
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
          <main id="main" className="flex flex-1 flex-col lg:flex-none">
            <Outlet />
          </main>
        </div>
      </div>
    </Screen>
  )
}

export function OnboardingLayout() {
  return (
    <Screen>
      <Outlet />
    </Screen>
  )
}
