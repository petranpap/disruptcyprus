import { useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { Icon } from '@/components/ui/Icon'

/** Invitation to personalize: guests go to sign-up, readers without industries to industry settings. */
export function PersonalizeCard({ signedIn }: { signedIn: boolean }) {
  const { t } = useTranslation()
  const navigate = useNavigate()

  return (
    <section className="flex flex-col gap-3 rounded-card bg-primary-fixed p-space-md text-on-primary-fixed-variant sm:flex-row sm:items-center">
      <Icon name="tune" size={28} className="shrink-0" />
      <div className="flex-1">
        <h2 className="font-headline text-headline-sm">
          {signedIn ? t('feed.fallbackTitle') : t('feed.personalizeTitle')}
        </h2>
        <p className="text-body-sm">{signedIn ? t('feed.fallbackBody') : t('feed.personalizeBody')}</p>
      </div>
      <Button block={false} onClick={() => navigate(signedIn ? '/settings/industries' : '/sign-up')}>
        {t('feed.personalizeCta')}
      </Button>
    </section>
  )
}
