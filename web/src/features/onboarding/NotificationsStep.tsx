import { useState } from 'react'
import { Trans, useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { useUpdateProfile } from '@/api/auth'
import { useNotificationPreferences, useUpdateNotificationPreferences } from '@/api/preferences'
import type { NotificationPreferences } from '@/api/schemas'
import { BottomDock } from '@/components/layout/BottomDock'
import { StepHeader } from '@/components/layout/StepHeader'
import { Button } from '@/components/ui/Button'
import { Icon } from '@/components/ui/Icon'
import { Skeleton } from '@/components/ui/Skeleton'
import { Switch } from '@/components/ui/Switch'
import { FormAlert } from '@/features/auth/AuthHeading'
import { isIos, isStandalone } from '@/hooks/useStandalone'
import { applyServerErrors } from '@/lib/forms'
import { DELIVERY_TIMES, PREFERENCE_TOGGLES, withSuggestions } from './constants'
import { OnboardingIntro } from './OnboardingIntro'

export function NotificationsStep() {
  const preferences = useNotificationPreferences()

  return (
    <NotificationsForm
      key={preferences.data ? 'loaded' : 'loading'}
      initial={preferences.data ? withSuggestions(preferences.data) : null}
    />
  )
}

function NotificationsForm({ initial }: { initial: NotificationPreferences | null }) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const updatePreferences = useUpdateNotificationPreferences()
  const updateProfile = useUpdateProfile()
  const [draft, setDraft] = useState<NotificationPreferences | null>(initial)
  const [formError, setFormError] = useState<string | null>(null)
  const showInstallHint = !isStandalone()

  const finish = async () => {
    if (!draft) return
    setFormError(null)
    try {
      await updatePreferences.mutateAsync(draft)
      await updateProfile.mutateAsync({ onboarding_completed: true })
      navigate('/', { replace: true })
    } catch (error) {
      setFormError(applyServerErrors(error, () => undefined, []))
    }
  }

  return (
    <>
      <StepHeader current={3} total={3} onBack={() => navigate('/onboarding/industries')} />
      <main id="main" className="mx-auto w-full max-w-narrow px-space-md pt-space-lg pb-36 lg:px-8 lg:pt-space-xl">
        <OnboardingIntro
          icon="notifications"
          kicker={t('onboarding.notifications.kicker')}
          title={t('onboarding.notifications.title')}
          subtitle={t('onboarding.notifications.subtitle')}
        />
        {formError && <FormAlert>{formError}</FormAlert>}

        <section
          className="rounded-card border border-card-stroke bg-surface-container-lowest px-space-md"
          aria-labelledby="digests-title"
        >
          <h2 id="digests-title" className="pt-space-md text-label-md tracking-wider text-on-surface-variant">
            {t('onboarding.notifications.digestsTitle')}
          </h2>
          {draft ? (
            <div className="divide-y divide-hairline">
              {PREFERENCE_TOGGLES.map((toggle) => (
                <Switch
                  key={toggle.key}
                  checked={draft[toggle.key]}
                  onChange={(checked) => setDraft({ ...draft, [toggle.key]: checked })}
                  label={t(`onboarding.notifications.${toggle.label}`)}
                  hint={t(`onboarding.notifications.${toggle.hint}`)}
                />
              ))}
              <label className="flex min-h-tap items-center justify-between gap-4 py-3">
                <span className="text-label-lg text-on-surface">{t('onboarding.notifications.deliveryTime')}</span>
                <select
                  value={draft.delivery_time}
                  onChange={(event) => setDraft({ ...draft, delivery_time: event.target.value })}
                  className="h-10 rounded-control border border-outline-variant/70 bg-surface-container-lowest px-3 text-body-sm text-on-surface focus:border-brand-cyan focus:outline-none"
                >
                  {DELIVERY_TIMES.map((time) => (
                    <option key={time} value={time}>
                      {time}
                    </option>
                  ))}
                </select>
              </label>
            </div>
          ) : (
            <div className="space-y-3 py-space-md">
              {PREFERENCE_TOGGLES.map((toggle) => (
                <Skeleton key={toggle.key} className="h-12 w-full" />
              ))}
            </div>
          )}
        </section>

        {showInstallHint && (
          <section className="mt-space-lg rounded-card bg-primary-fixed p-space-md text-on-primary-fixed-variant">
            <h2 className="flex items-center gap-2 font-headline text-headline-sm">
              <Icon name="ios_share" size={20} />
              {t('onboarding.notifications.installTitle')}
            </h2>
            <p className="mt-space-sm text-body-sm">
              {isIos() ? (
                <Trans
                  i18nKey="onboarding.notifications.installIos"
                  components={{
                    share: <Icon name="ios_share" size={16} className="-mt-0.5 inline" label={t('common.share')} />,
                  }}
                />
              ) : (
                t('onboarding.notifications.installOther')
              )}
            </p>
          </section>
        )}
      </main>
      <BottomDock>
        <Button
          className="sm:ml-auto sm:w-auto sm:min-w-60"
          onClick={() => void finish()}
          disabled={!draft}
          loading={updatePreferences.isPending || updateProfile.isPending}
          trailingIcon={<Icon name="check" size={18} />}
        >
          {t('onboarding.notifications.finish')}
        </Button>
      </BottomDock>
    </>
  )
}
