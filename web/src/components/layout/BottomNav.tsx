import { NavLink, useLocation } from 'react-router'
import { useTranslation } from 'react-i18next'
import { Icon, type IconName } from '@/components/ui/Icon'
import { cn } from '@/lib/cn'

const HOME_PATHS = ['/', '/news', '/startups', '/research', '/investors', '/events']

interface Item {
  to: string
  label: string
  icon: IconName
  activeIcon: IconName
  isActive: (pathname: string) => boolean
}

/** Phone/tablet bottom navigation: Home, Explore, Saved, Profile (active item filled). Hidden on desktop. */
export function BottomNav() {
  const { t } = useTranslation()
  const { pathname } = useLocation()

  const items: Item[] = [
    {
      to: '/',
      label: t('nav.home'),
      icon: 'home',
      activeIcon: 'home-fill',
      isActive: (path) => HOME_PATHS.includes(path),
    },
    {
      to: '/explore',
      label: t('nav.explore'),
      icon: 'explore',
      activeIcon: 'explore-fill',
      isActive: (path) => path.startsWith('/explore'),
    },
    {
      to: '/saved',
      label: t('nav.saved'),
      icon: 'bookmark',
      activeIcon: 'bookmark-fill',
      isActive: (path) => path.startsWith('/saved'),
    },
    {
      to: '/profile',
      label: t('nav.profile'),
      icon: 'person',
      activeIcon: 'person-fill',
      isActive: (path) => path.startsWith('/profile') || path.startsWith('/settings'),
    },
  ]

  return (
    <nav
      aria-label={t('nav.main')}
      className="fixed inset-x-0 bottom-0 z-50 glass pb-safe shadow-[0_-4px_20px_-2px_rgba(15,23,42,0.05)] hairline-t lg:hidden"
    >
      <ul className="flex items-center justify-around px-space-md pt-1">
        {items.map((item) => {
          const active = item.isActive(pathname)

          return (
            <li key={item.to}>
              <NavLink
                to={item.to}
                aria-current={active ? 'page' : undefined}
                className={cn(
                  'flex min-h-tap min-w-16 flex-col items-center justify-center py-1 transition-[color,transform] duration-150 active:scale-95',
                  active ? 'font-semibold text-primary' : 'font-medium text-on-surface-variant hover:text-primary',
                )}
              >
                <Icon name={active ? item.activeIcon : item.icon} size={24} />
                <span className="mt-0.5 text-label-sm">{item.label}</span>
              </NavLink>
            </li>
          )
        })}
      </ul>
    </nav>
  )
}
