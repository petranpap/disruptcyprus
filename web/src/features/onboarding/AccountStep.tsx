import { useState } from 'react'
import { useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useMe, useUpdateProfile } from '@/api/auth'
import type { Locale } from '@/api/schemas'
import { BottomDock } from '@/components/layout/BottomDock'
import { StepHeader } from '@/components/layout/StepHeader'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { Icon } from '@/components/ui/Icon'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { TextField } from '@/components/ui/TextField'
import { FormAlert } from '@/features/auth/AuthHeading'
import { LegalText } from '@/features/auth/LegalLinks'
import { useLocale } from '@/hooks/useLocale'
import { applyServerErrors } from '@/lib/forms'
import { OnboardingIntro } from './OnboardingIntro'

export function AccountStep() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { data: user } = useMe()
  const { locale, setLocale } = useLocale()
  const updateProfile = useUpdateProfile()

  const [name, setName] = useState(user?.name ?? '')
  const [contentLocales, setContentLocales] = useState<Locale[]>(user?.content_locales ?? ['el', 'en'])
  const [consent, setConsent] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [formError, setFormError] = useState<string | null>(null)

  const needsConsent = user?.needs_consent ?? false

  const toggleContentLocale = (value: Locale) =>
    setContentLocales((current) =>
      current.includes(value) ? current.filter((item) => item !== value) : [...current, value],
    )

  const submit = async () => {
    const nextErrors: Record<string, string> = {}
    if (!name.trim()) nextErrors.name = t('validation.required')
    if (contentLocales.length === 0) nextErrors.content_locales = t('validation.contentLocales')
    if (needsConsent && !consent) nextErrors.consent = t('validation.consent')
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length > 0) return

    setFormError(null)
    try {
      await updateProfile.mutateAsync({
        name: name.trim(),
        locale,
        content_locales: contentLocales,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        ...(needsConsent ? { consent: true as const } : {}),
      })
      navigate('/onboarding/industries')
    } catch (error) {
      setFormError(
        applyServerErrors(
          error,
          (field, { message }) => setErrors((current) => ({ ...current, [field]: message ?? '' })),
          ['name', 'content_locales', 'consent'],
        ),
      )
    }
  }

  return (
    <>
      <StepHeader current={1} total={3} />
      <main id="main" className="mx-auto w-full max-w-narrow px-space-md pt-space-lg pb-36 lg:px-8 lg:pt-space-xl">
        <OnboardingIntro
          icon="auto_awesome"
          kicker={t('onboarding.account.kicker')}
          title={t('onboarding.account.title')}
          subtitle={t('onboarding.account.subtitle')}
        />
        <div className="flex flex-col gap-space-lg">
          {formError && <FormAlert>{formError}</FormAlert>}
          <TextField
            label={t('auth.name')}
            value={name}
            onChange={(event) => setName(event.target.value)}
            autoComplete="name"
            error={errors.name}
          />

          <div className="flex flex-col gap-1.5">
            <p className="flex items-center gap-1.5 text-label-lg text-on-surface">
              <Icon name="language" size={16} className="text-primary" />
              {t('onboarding.account.uiLanguage')}
            </p>
            <SegmentedControl
              label={t('onboarding.account.uiLanguage')}
              value={locale}
              onChange={(next) => void setLocale(next, { sync: false })}
              options={[
                { value: 'el', label: t('language.el') },
                { value: 'en', label: t('language.en') },
              ]}
            />
          </div>

          <fieldset className="flex flex-col">
            <legend className="text-label-lg text-on-surface">{t('onboarding.account.contentLanguages')}</legend>
            <Checkbox
              label={t('language.el')}
              checked={contentLocales.includes('el')}
              onChange={() => toggleContentLocale('el')}
            />
            <Checkbox
              label={t('language.en')}
              checked={contentLocales.includes('en')}
              onChange={() => toggleContentLocale('en')}
            />
            <p
              className={errors.content_locales ? 'text-body-sm text-error' : 'text-body-sm text-outline'}
              role={errors.content_locales ? 'alert' : undefined}
            >
              {errors.content_locales ?? t('onboarding.account.contentLanguagesHint')}
            </p>
          </fieldset>

          {needsConsent && (
            <section className="rounded-card border border-card-stroke bg-surface-container-lowest p-space-md">
              <h2 className="font-headline text-headline-sm text-on-surface">{t('onboarding.account.consentTitle')}</h2>
              <Checkbox
                label={<LegalText i18nKey="auth.consent" />}
                checked={consent}
                onChange={(event) => setConsent(event.target.checked)}
                error={errors.consent}
              />
            </section>
          )}
        </div>
      </main>
      <BottomDock>
        <Button
          className="sm:ml-auto sm:w-auto sm:min-w-60"
          onClick={() => void submit()}
          loading={updateProfile.isPending}
          trailingIcon={<Icon name="arrow_forward" size={18} />}
        >
          {t('common.continue')}
        </Button>
      </BottomDock>
    </>
  )
}
