import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import { Logo } from '@/components/ui/Logo'
import { LanguageToggle } from '@/features/auth/LanguageToggle'
import { SITE_URL } from '@/lib/env'
import type { SectionTab } from './SiteHeader'

/** Website footer (desktop). On phones the app-style bottom navigation takes its place. */
export function SiteFooter({ tabs }: { tabs: SectionTab[] }) {
  const { t } = useTranslation()
  const year = new Date().getFullYear()
  const linkClass = 'text-body-sm text-on-surface-variant transition-colors hover:text-primary'

  return (
    <footer className="mt-space-xl hidden bg-surface-container-lowest hairline-t lg:block">
      <nav
        aria-label={t('footer.sitemap')}
        className="mx-auto grid w-full max-w-content grid-cols-[2fr_1fr_1fr] gap-10 px-8 py-12"
      >
        <div className="max-w-sm space-y-4">
          <Logo size="sm" />
          <p className="font-body text-body-md text-on-surface-variant">{t('footer.tagline')}</p>
          <LanguageToggle className="w-40" />
        </div>
        <div>
          <h2 className="mb-3 text-label-md tracking-wider text-outline uppercase">{t('footer.sections')}</h2>
          <ul className="space-y-2">
            {tabs.map((tab) => (
              <li key={tab.to}>
                <Link to={tab.to} className={linkClass}>
                  {tab.label}
                </Link>
              </li>
            ))}
          </ul>
        </div>
        <div>
          <h2 className="mb-3 text-label-md tracking-wider text-outline uppercase">{t('footer.company')}</h2>
          <ul className="space-y-2">
            <li>
              <a href={`${SITE_URL}/about`} className={linkClass}>
                {t('footer.about')}
              </a>
            </li>
            <li>
              <a href={`${SITE_URL}/contact`} className={linkClass}>
                {t('footer.contact')}
              </a>
            </li>
            <li>
              <a href={`${SITE_URL}/privacy`} className={linkClass}>
                {t('footer.privacy')}
              </a>
            </li>
            <li>
              <a href={`${SITE_URL}/terms`} className={linkClass}>
                {t('footer.terms')}
              </a>
            </li>
          </ul>
        </div>
      </nav>
      <div className="hairline-t">
        <div className="mx-auto flex w-full max-w-content items-center justify-between px-8 py-4 text-label-sm text-outline">
          <span>{t('footer.rights', { year })}</span>
          <span>{t('footer.madeIn')}</span>
        </div>
      </div>
    </footer>
  )
}
