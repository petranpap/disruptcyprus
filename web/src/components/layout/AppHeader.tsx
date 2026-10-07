import { Link, NavLink } from 'react-router'
import { useTranslation } from 'react-i18next'
import { Icon } from '@/components/ui/Icon'
import { Logo } from '@/components/ui/Logo'
import { cn } from '@/lib/cn'

export interface SectionTab {
  to: string
  label: string
  end?: boolean
}

interface AppHeaderProps {
  tabs: SectionTab[]
  unreadCount?: number
}

/** Sticky frosted header: logo, search, bell (unread dot) and the horizontally scrolling section tabs. */
export function AppHeader({ tabs, unreadCount = 0 }: AppHeaderProps) {
  const { t } = useTranslation()

  return (
    <header className="sticky top-0 z-40 glass pt-safe hairline-b">
      <div className="flex items-center justify-between px-space-md py-space-xs">
        <Link to="/" className="flex min-h-tap items-center" aria-label={t('common.appName')}>
          <Logo size="sm" />
        </Link>
        <div className="-mr-2 flex items-center">
          <Link
            to="/explore?focus=search"
            aria-label={t('nav.search')}
            className="inline-flex size-tap items-center justify-center rounded-pill text-on-surface-variant transition-colors hover:text-primary"
          >
            <Icon name="search" size={22} />
          </Link>
          <Link
            to="/notifications"
            aria-label={unreadCount > 0 ? t('nav.notificationsUnread', { count: unreadCount }) : t('nav.notifications')}
            className="relative inline-flex size-tap items-center justify-center rounded-pill text-on-surface-variant transition-colors hover:text-primary"
          >
            <Icon name="notifications" size={22} />
            {unreadCount > 0 && (
              <span
                className="absolute top-2.5 right-2.5 size-2 rounded-pill bg-secondary-container ring-2 ring-surface-container-lowest"
                aria-hidden="true"
              />
            )}
          </Link>
        </div>
      </div>
      <nav aria-label={t('nav.sections')} className="no-scrollbar flex gap-6 overflow-x-auto px-space-md">
        {tabs.map((tab) => (
          <NavLink
            key={tab.to}
            to={tab.to}
            end={tab.end}
            className={({ isActive }) =>
              cn(
                'relative flex min-h-tap items-center text-label-lg whitespace-nowrap transition-colors',
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
    </header>
  )
}
