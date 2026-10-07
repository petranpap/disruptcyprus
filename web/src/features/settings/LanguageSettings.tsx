import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMe, useUpdateProfile } from '@/api/auth'
import type { Locale } from '@/api/schemas'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { useLocale } from '@/hooks/useLocale'
import { useUiStore } from '@/stores/ui'
import { SettingsCard, SettingsPage } from './SettingsLayout'

/** UI language (instant, synced) and, for readers, the languages stories are shown in. */
export function LanguageSettings() {
  const { t } = useTranslation()
  const { data: user } = useMe()
  const { locale, setLocale } = useLocale()
  const update = useUpdateProfile()
  const showToast = useUiStore((state) => state.showToast)
  const [contentLocales, setContentLocales] = useState<Locale[]>(user?.content_locales ?? ['el', 'en'])
  const [error, setError] = useState<string | null>(null)

  const toggle = (value: Locale) =>
    setContentLocales((current) =>
      current.includes(value) ? current.filter((item) => item !== value) : [...current, value],
    )

  return (
    <SettingsPage title={t('settings.language.title')}>
      <SettingsCard title={t('onboarding.account.uiLanguage')}>
        <SegmentedControl<Locale>
          label={t('onboarding.account.uiLanguage')}
          value={locale}
          onChange={(next) => void setLocale(next)}
          options={[
            { value: 'el', label: t('language.el') },
            { value: 'en', label: t('language.en') },
          ]}
        />
      </SettingsCard>
      {user && (
        <SettingsCard title={t('onboarding.account.contentLanguages')}>
          <Checkbox label={t('language.el')} checked={contentLocales.includes('el')} onChange={() => toggle('el')} />
          <Checkbox label={t('language.en')} checked={contentLocales.includes('en')} onChange={() => toggle('en')} />
          <p
            className={error ? 'text-body-sm text-error' : 'text-body-sm text-outline'}
            role={error ? 'alert' : undefined}
          >
            {error ?? t('onboarding.account.contentLanguagesHint')}
          </p>
          <Button
            className="mt-space-md"
            loading={update.isPending}
            onClick={() => {
              if (contentLocales.length === 0) {
                setError(t('validation.contentLocales'))
                return
              }
              setError(null)
              update.mutate({ content_locales: contentLocales }, { onSuccess: () => showToast(t('settings.saved')) })
            }}
          >
            {t('common.save')}
          </Button>
        </SettingsCard>
      )}
    </SettingsPage>
  )
}
