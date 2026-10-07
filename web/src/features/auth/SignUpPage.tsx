import { zodResolver } from '@hookform/resolvers/zod'
import { useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { useSignUp } from '@/api/auth'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { PasswordField, TextField } from '@/components/ui/TextField'
import { currentLocale } from '@/i18n'
import { applyServerErrors } from '@/lib/forms'
import { PASSWORD_RULE } from '@/lib/validation'
import { AuthHeading, FormAlert } from './AuthHeading'
import { GoogleButton } from './GoogleButton'
import { LegalText } from './LegalLinks'

export function SignUpPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const signUp = useSignUp()
  const [formError, setFormError] = useState<string | null>(null)

  const schema = useMemo(
    () =>
      z.object({
        name: z.string().trim().min(1, t('validation.required')).max(120),
        email: z.email(t('validation.email')),
        password: z.string().regex(PASSWORD_RULE, t('validation.passwordRule')),
        consent: z.boolean().refine((value) => value, t('validation.consent')),
      }),
    [t],
  )
  type Values = z.infer<typeof schema>

  const { register, handleSubmit, setError, formState } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', email: '', password: '', consent: false },
  })

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null)
    try {
      await signUp.mutateAsync({
        ...values,
        locale: currentLocale(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      })
      navigate('/onboarding', { replace: true })
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['name', 'email', 'password', 'consent']))
    }
  })

  return (
    <>
      <AuthHeading title={t('auth.signUpTitle')} subtitle={t('auth.signUpSubtitle')} />
      <GoogleButton />
      <div className="my-space-md flex items-center gap-3 text-label-sm text-outline" aria-hidden="true">
        <span className="h-px flex-1 bg-outline-variant/50" />
        {t('common.or')}
        <span className="h-px flex-1 bg-outline-variant/50" />
      </div>
      <form onSubmit={onSubmit} noValidate className="flex flex-col gap-space-md">
        {formError && <FormAlert>{formError}</FormAlert>}
        <TextField
          label={t('auth.name')}
          autoComplete="name"
          error={formState.errors.name?.message}
          {...register('name')}
        />
        <TextField
          label={t('auth.email')}
          type="email"
          autoComplete="email"
          inputMode="email"
          error={formState.errors.email?.message}
          {...register('email')}
        />
        <PasswordField
          label={t('auth.password')}
          autoComplete="new-password"
          hint={t('auth.passwordHint')}
          error={formState.errors.password?.message}
          {...register('password')}
        />
        <Checkbox
          label={<LegalText i18nKey="auth.consent" />}
          error={formState.errors.consent?.message}
          {...register('consent')}
        />
        <Button type="submit" loading={formState.isSubmitting}>
          {t('welcome.createAccount')}
        </Button>
      </form>
      <p className="mt-auto pt-space-lg text-center text-body-sm text-on-surface-variant">
        {t('auth.haveAccount')}{' '}
        <Link to="/sign-in" className="font-semibold text-primary hover:underline">
          {t('welcome.signIn')}
        </Link>
      </p>
    </>
  )
}
