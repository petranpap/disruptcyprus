import { Link, useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useMe, useSignOut } from '@/api/auth'
import { useIndustryList } from '@/api/content'
import { clearPersonalCaches } from '@/api/offline'
import { useMyIndustries } from '@/api/taxonomy'
import { Button, ButtonLink } from '@/components/ui/Button'
import { Chip } from '@/components/ui/Chip'
import { Icon, type IconName } from '@/components/ui/Icon'

interface Row {
  to: string
  icon: IconName
  label: string
  authOnly?: boolean
}

/** /profile — account summary, followed industries and the settings list. */
export function ProfilePage() {
  const { t, i18n } = useTranslation()
  const navigate = useNavigate()
  const { data: user } = useMe()
  const signOut = useSignOut()
  const mine = useMyIndustries(Boolean(user))
  const industries = useIndustryList(i18n.language)
  const followed = (industries.data ?? []).filter((industry) => mine.data?.industry_ids.includes(industry.id))

  const rows: Row[] = [
    { to: '/settings/industries', icon: 'tune', label: t('settings.industries.title'), authOnly: true },
    { to: '/settings/notifications', icon: 'notifications', label: t('settings.notifications.title'), authOnly: true },
    { to: '/settings/language', icon: 'language', label: t('settings.language.title') },
    { to: '/settings/appearance', icon: 'dark_mode', label: t('settings.appearance.title') },
    { to: '/settings/account', icon: 'person', label: t('settings.account.title'), authOnly: true },
    { to: '/settings/privacy', icon: 'visibility_off', label: t('settings.privacy.title'), authOnly: true },
  ]

  return (
    <div className="mx-auto flex w-full max-w-narrow flex-col gap-space-lg">
      <h1 className="font-headline text-headline-hero-mobile text-on-surface lg:text-headline-hero">
        {t('nav.profile')}
      </h1>

      {user ? (
        <section className="flex items-center gap-3 rounded-card border border-card-stroke bg-surface-container-lowest p-space-md">
          <span className="flex size-14 items-center justify-center overflow-hidden rounded-pill bg-primary-fixed text-on-primary-fixed-variant">
            {user.avatar_url ? (
              <img src={user.avatar_url} alt="" className="size-full object-cover" />
            ) : (
              <Icon name="person" size={28} />
            )}
          </span>
          <div className="min-w-0">
            <p className="truncate font-headline text-headline-sm text-on-surface">{user.name}</p>
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

      {user && (
        <section className="space-y-2">
          <div className="flex items-center justify-between">
            <h2 className="text-label-md tracking-wider text-on-surface-variant uppercase">
              {t('profile.industries')}
            </h2>
            <Link to="/settings/industries" className="min-h-tap py-3 text-label-md text-primary hover:underline">
              {t('profile.edit')}
            </Link>
          </div>
          {followed.length > 0 ? (
            <div className="flex flex-wrap gap-2">
              {followed.map((industry) => (
                <Chip key={industry.id} color={industry.color} onClick={() => navigate(`/explore/${industry.slug}`)}>
                  {industry.name}
                </Chip>
              ))}
            </div>
          ) : (
            <p className="text-body-sm text-outline">{t('profile.noIndustries')}</p>
          )}
        </section>
      )}

      <nav aria-label={t('profile.settings')}>
        <ul className="divide-y divide-hairline overflow-hidden rounded-card border border-card-stroke bg-surface-container-lowest">
          {rows
            .filter((row) => user || !row.authOnly)
            .map((row) => (
              <li key={row.to}>
                <Link
                  to={row.to}
                  className="flex min-h-tap items-center gap-3 px-space-md py-3.5 text-label-lg text-on-surface hover:bg-surface-container-low"
                >
                  <Icon name={row.icon} size={20} className="text-primary" />
                  <span className="flex-1">{row.label}</span>
                  <Icon name="chevron_right" size={20} className="text-outline" />
                </Link>
              </li>
            ))}
        </ul>
      </nav>

      {user && (
        <Button
          variant="danger"
          loading={signOut.isPending}
          onClick={() =>
            signOut.mutate(undefined, {
              onSettled: () => {
                // Offline copies belong to this reader: never leave them on a shared device.
                void clearPersonalCaches()
                navigate('/welcome', { replace: true })
              },
            })
          }
        >
          {t('auth.signOut')}
        </Button>
      )}
    </div>
  )
}
