import { useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useMe, useSignOut } from '@/api/auth'
import { Button, ButtonLink } from '@/components/ui/Button'
import { Icon } from '@/components/ui/Icon'
import { LanguageToggle } from '@/features/auth/LanguageToggle'
import { ThemeToggle } from './ThemeToggle'

/** Phase 4 profile: account summary, language and theme. Full settings arrive in Phase 5. */
export function ProfilePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { data: user } = useMe()
  const signOut = useSignOut()

  return (
    <div className="flex flex-col gap-space-lg">
      <h1 className="font-headline text-headline-hero-mobile text-on-surface">{t('nav.profile')}</h1>

      {user ? (
        <section className="flex items-center gap-3 rounded-card border border-card-stroke bg-surface-container-lowest p-space-md">
          <span className="flex size-12 items-center justify-center rounded-pill bg-primary-fixed text-on-primary-fixed-variant">
            <Icon name="person" size={26} />
          </span>
          <div className="min-w-0">
            <p className="truncate text-label-lg text-on-surface">{user.name}</p>
            <p className="truncate text-body-sm text-on-surface-variant">{user.email}</p>
          </div>
        </section>
      ) : (
        <section className="flex flex-col gap-3 rounded-card border border-card-stroke bg-surface-container-lowest p-space-md">
          <p className="font-body text-body-md text-on-surface-variant">{t('auth.signUpSubtitle')}</p>
          <ButtonLink to="/sign-up">{t('welcome.createAccount')}</ButtonLink>
          <ButtonLink to="/sign-in" variant="outline">
            {t('welcome.signIn')}
          </ButtonLink>
        </section>
      )}

      <section className="flex flex-col gap-2">
        <h2 className="text-label-md tracking-wider text-on-surface-variant">{t('language.label')}</h2>
        <LanguageToggle />
      </section>

      <section className="flex flex-col gap-2">
        <h2 className="text-label-md tracking-wider text-on-surface-variant">{t('theme.label')}</h2>
        <ThemeToggle />
      </section>

      {user && (
        <Button
          variant="danger"
          loading={signOut.isPending}
          onClick={() => signOut.mutate(undefined, { onSettled: () => navigate('/welcome', { replace: true }) })}
        >
          {t('auth.signOut')}
        </Button>
      )}
    </div>
  )
}
