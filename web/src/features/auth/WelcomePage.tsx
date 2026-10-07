import { Link, useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useTrending } from '@/api/content'
import { AppColumn } from '@/components/layout/AppColumn'
import { ButtonLink } from '@/components/ui/Button'
import { Icon } from '@/components/ui/Icon'
import { Logo } from '@/components/ui/Logo'
import { Skeleton } from '@/components/ui/Skeleton'
import { upper } from '@/lib/greek'
import { STORAGE_KEYS, storage } from '@/lib/storage'
import { GoogleButton } from './GoogleButton'
import { LanguageToggle } from './LanguageToggle'
import { LegalText } from './LegalLinks'

function PreviewCard() {
  const { t, i18n } = useTranslation()
  const { data, isPending } = useTrending(i18n.language)
  const article = data?.[0]

  if (isPending) return <Skeleton className="h-[74px] w-full rounded-card" />
  if (!article) return null

  return (
    <Link
      to={`/articles/${article.slug}`}
      onClick={() => storage.set(STORAGE_KEYS.welcomed, '1')}
      className="flex w-full items-center gap-space-sm rounded-card border border-outline-variant/30 bg-surface-container-lowest/80 p-space-sm text-left shadow-sm backdrop-blur-sm"
    >
      <div className="size-14 shrink-0 overflow-hidden rounded-control bg-surface-container">
        {article.image && <img src={article.image.thumb} alt="" className="size-full object-cover" />}
      </div>
      <div className="min-w-0 flex-1 pr-1">
        <div className="mb-0.5 flex items-center gap-1.5 overflow-hidden whitespace-nowrap">
          <span className="text-label-sm font-bold tracking-wider text-secondary">
            {upper(t('welcome.previewKicker'))}
          </span>
          {article.reading_time_minutes && (
            <>
              <span className="text-xs text-outline" aria-hidden="true">
                •
              </span>
              <span className="text-label-sm text-on-surface-variant">
                {t('common.minRead', { count: article.reading_time_minutes })}
              </span>
            </>
          )}
        </div>
        <p className="truncate font-headline text-headline-sm leading-snug text-on-surface" lang={article.locale}>
          {article.title}
        </p>
      </div>
      <Icon name="arrow_forward" size={18} className="shrink-0 text-brand-cyan" />
    </Link>
  )
}

/** First-run screen, following UI/disrupt_cyprus_welcome with the approved startup-focused copy. */
export function WelcomePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()

  const continueAsGuest = () => {
    storage.set(STORAGE_KEYS.welcomed, '1')
    navigate('/', { replace: true })
  }

  return (
    <AppColumn className="overflow-hidden">
      <div
        className="pointer-events-none absolute -top-24 -left-20 size-64 rounded-pill bg-brand-cyan/20 blur-3xl"
        aria-hidden="true"
      />
      <div
        className="pointer-events-none absolute top-1/3 -right-24 size-60 rounded-pill bg-brand-crimson/15 blur-3xl"
        aria-hidden="true"
      />

      <div className="relative z-10 flex min-h-dvh flex-col justify-between px-space-md py-space-lg pt-safe">
        <header className="flex items-center justify-between pt-space-xs pb-space-xs hairline-b">
          <div className="flex items-center gap-1.5">
            <span className="size-2 rounded-pill bg-secondary" aria-hidden="true" />
            <span className="text-label-md tracking-wider text-on-surface-variant">{upper(t('welcome.edition'))}</span>
          </div>
          <span className="text-label-sm tracking-wider text-outline">{upper(t('welcome.tagline'))}</span>
        </header>

        <main id="main" className="my-space-lg flex flex-1 flex-col items-center justify-center text-center">
          <Logo size="lg" className="mb-space-md" />
          <p className="mb-space-md inline-flex items-center rounded-pill border border-outline-variant/40 bg-surface-container-high/60 px-3.5 py-1.5 text-label-md font-semibold tracking-wide text-primary shadow-sm backdrop-blur-sm">
            {t('welcome.badge')}
          </p>
          <h1 className="mx-auto mb-space-sm max-w-xs font-headline text-headline-hero-mobile text-on-surface">
            {t('welcome.headline')}
          </h1>
          <p className="mb-space-md max-w-sm font-body text-body-md text-on-surface-variant">{t('welcome.subtitle')}</p>
          <PreviewCard />
        </main>

        <footer className="flex flex-col gap-3 pt-space-xs">
          <ButtonLink to="/sign-up" trailingIcon={<Icon name="chevron_right" size={18} />}>
            {t('welcome.createAccount')}
          </ButtonLink>
          <ButtonLink to="/sign-in" variant="outline">
            {t('welcome.signIn')}
          </ButtonLink>
          <GoogleButton />
          <button
            type="button"
            onClick={continueAsGuest}
            className="mx-auto inline-flex min-h-tap items-center gap-1 text-label-md font-medium tracking-wide text-on-surface-variant transition-colors hover:text-primary"
          >
            {t('welcome.guest')}
            <Icon name="east" size={14} />
          </button>
          <LanguageToggle className="mx-auto w-40" />
          <p className="text-center text-label-sm text-outline">
            <LegalText i18nKey="welcome.legal" />
          </p>
        </footer>
      </div>
    </AppColumn>
  )
}
