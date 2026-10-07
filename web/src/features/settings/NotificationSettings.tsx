import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNotificationPreferences, useUpdateNotificationPreferences } from '@/api/preferences'
import type { NotificationPreferences } from '@/api/schemas'
import { Button } from '@/components/ui/Button'
import { Skeleton } from '@/components/ui/Skeleton'
import { Switch } from '@/components/ui/Switch'
import { DELIVERY_TIMES, PREFERENCE_TOGGLES } from '@/features/onboarding/constants'
import { useUiStore } from '@/stores/ui'
import { SettingsCard, SettingsPage } from './SettingsLayout'

function PreferencesForm({ initial }: { initial: NotificationPreferences }) {
  const { t } = useTranslation()
  const update = useUpdateNotificationPreferences()
  const showToast = useUiStore((state) => state.showToast)
  const [draft, setDraft] = useState(initial)

  return (
    <>
      <SettingsCard title={t('onboarding.notifications.digestsTitle')}>
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
      </SettingsCard>
      <p className="text-body-sm text-outline">{t('settings.notifications.pushNote')}</p>
      <Button
        loading={update.isPending}
        onClick={() =>
          update.mutate(draft, {
            onSuccess: () => showToast(t('settings.saved')),
            onError: () => showToast(t('errors.generic')),
          })
        }
      >
        {t('common.save')}
      </Button>
    </>
  )
}

export function NotificationSettings() {
  const { t } = useTranslation()
  const preferences = useNotificationPreferences()

  return (
    <SettingsPage title={t('settings.notifications.title')} description={t('onboarding.notifications.subtitle')}>
      {preferences.data ? <PreferencesForm initial={preferences.data} /> : <Skeleton className="h-72 rounded-card" />}
    </SettingsPage>
  )
}
