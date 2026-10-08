import { useState, type FormEvent } from 'react'
import { Link, NavLink, useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useUnreadCount } from '@/api/notifications'
import type { User } from '@/api/schemas'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Icon } from '@/components/ui/Icon'
import { Logo } from '@/components/ui/Logo'
import { useLocale } from '@/hooks/useLocale'
import { cn } from '@/lib/cn'

export interface SectionTab {
  to: string
  label: string
  end?: boolean
}

interface SiteHeaderProps {
  tabs: SectionTab[]
  user: User | null | undefined
}

/** Shared icon-link look; each link adds its own display utility so responsive visibility never conflicts. */
const iconLink =
  'relative size-tap items-center justify-center rounded-pill text-on-surface-variant transition-colors hover:text-primary'

/**
 * One header for every width (no duplicated navigation):
 *  - phones/tablets: logo + icon actions, section tabs wrap to a scrolling second row (app feel);
 *  - desktop (lg+): a website top bar — logo, inline section navigation, search field, language,
 *    saved, notifications and the account (or sign-in buttons for guests).
 */
export function SiteHeader({ tabs, user }: SiteHeaderProps) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { locale, setLocale } = useLocale()
  const [query, setQuery] = useState('')
  const unreadCount = useUnreadCount(Boolean(user)).data ?? 0

  const search = (event: FormEvent) => {
    event.preventDefault()
    navigate(query.trim() ? `/explore?q=${encodeURIComponent(query.trim())}` : '/explore?focus=search')
  }

  return (
    <header className="sticky top-0 z-40 glass pt-safe hairline-b">
      <div className="mx-auto flex w-full max-w-content flex-wrap items-center px-space-md lg:h-16 lg:flex-nowrap lg:gap-6 lg:px-8 xl:gap-8">
        <Link to="/" className="flex min-h-tap shrink-0 items-center py-space-xs" aria-label={t('common.appName')}>
          <Logo size="sm" />
        </Link>

        <nav
          aria-label={t('nav.sections')}
          className="order-last -mx-space-md no-scrollbar flex w-[calc(100%+2rem)] gap-6 overflow-x-auto px-space-md lg:order-none lg:mx-0 lg:w-auto lg:flex-1 lg:overflow-visible lg:px-0"
        >
          {tabs.map((tab) => (
            <NavLink
              key={tab.to}
              to={tab.to}
              end={tab.end}
              className={({ isActive }) =>
                cn(
                  'relative flex min-h-tap items-center text-label-lg whitespace-nowrap transition-colors lg:h-16',
                  isActive ? 'font-bold text-primary' : 'text-outline hover:text-on-surface',
                )
              }
            >
              {({ isActive }) => (
                <>
                  {tab.label}
                  {isActive && (
                    <span
                      className="absolute inset-x-0 bottom-0 h-[2.5px] rounded-pill bg-brand-cyan shadow-tab-glow"
                      aria-hidden="true"
                    />
                  )}
                </>
              )}
            </NavLink>
          ))}
        </nav>

        <div className="-mr-2 ml-auto flex items-center lg:mr-0 lg:gap-1">
          <form role="search" onSubmit={search} className="relative mr-2 hidden xl:block">
            <label htmlFor="site-search" className="sr-only">
              {t('nav.searchPlaceholder')}
            </label>
            <Icon
              name="search"
              size={18}
              className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-outline"
            />
            <input
              id="site-search"
              type="search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder={t('nav.searchPlaceholder')}
              className="h-10 w-52 rounded-pill border border-outline-variant/60 bg-surface-container-lowest pr-3 pl-9 text-body-sm text-on-surface placeholder:text-outline focus:border-brand-cyan focus:ring-2 focus:ring-brand-cyan/25 focus:outline-none"
            />
          </form>
          <Link
            to="/explore?focus=search"
            aria-label={t('nav.search')}
            className={cn(iconLink, 'inline-flex xl:hidden')}
          >
            <Icon name="search" size={22} />
          </Link>

          <button
            type="button"
            onClick={() => void setLocale(locale === 'el' ? 'en' : 'el')}
            className="hidden min-h-tap rounded-pill px-2 text-label-lg text-on-surface-variant transition-colors hover:text-primary lg:inline-flex lg:items-center"
            aria-label={t('language.label')}
          >
            {locale === 'el' ? t('language.short_en') : t('language.short_el')}
          </button>

          {user && (
            <Link to="/saved" aria-label={t('nav.saved')} className={cn(iconLink, 'hidden lg:inline-flex')}>
              <Icon name="bookmark" size={22} />
            </Link>
          )}

          <Link
            to="/notifications"
            aria-label={unreadCount > 0 ? t('nav.notificationsUnread', { count: unreadCount }) : t('nav.notifications')}
            className={cn(iconLink, 'inline-flex')}
          >
            <Icon name="notifications" size={22} />
            {unreadCount > 0 && (
              <span
                className="absolute top-2.5 right-2.5 size-2 rounded-pill bg-secondary-container ring-2 ring-surface-container-lowest"
                aria-hidden="true"
              />
            )}
          </Link>

          {user ? (
            <Link
              to="/profile"
              className="ml-1 hidden min-h-tap items-center gap-2 rounded-pill py-1 pr-3 pl-1 text-label-lg text-on-surface transition-colors hover:bg-surface-container-low lg:inline-flex"
            >
              <span className="flex size-8 items-center justify-center overflow-hidden rounded-pill bg-primary-fixed text-on-primary-fixed-variant">
                {user.avatar_url ? (
                  <img src={user.avatar_url} alt="" className="size-full object-cover" />
                ) : (
                  <Icon name="person" size={18} />
                )}
              </span>
              <span className="max-w-32 truncate">{user.name}</span>
            </Link>
          ) : (
            <div className="ml-2 hidden items-center gap-2 lg:flex">
              <Link to="/sign-in" className={cn(buttonClasses('outline', false), 'min-h-10 py-2 whitespace-nowrap')}>
                {t('nav.signIn')}
              </Link>
              <Link to="/sign-up" className={cn(buttonClasses('primary', false), 'min-h-10 py-2 whitespace-nowrap')}>
                {t('nav.signUp')}
              </Link>
            </div>
          )}
        </div>
      </div>
    </header>
  )
}
