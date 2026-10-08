import { zodResolver } from '@hookform/resolvers/zod'
import { useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { useSignIn } from '@/api/auth'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { PasswordField, TextField } from '@/components/ui/TextField'
import { applyServerErrors } from '@/lib/forms'
import { AuthHeading, FormAlert } from './AuthHeading'
import { GoogleButton } from './GoogleButton'

/** Error codes the OAuth callback sends back to /sign-in. */
const SOCIAL_ERRORS = {
  social_failed: 'auth.socialFailed',
  social_cancelled: 'auth.socialCancelled',
  social_unavailable: 'auth.socialUnavailable',
  social_unverified: 'auth.socialUnverified',
} as const

export function SignInPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const [params] = useSearchParams()
  const signIn = useSignIn()
  const [formError, setFormError] = useState<string | null>(() => {
    const code = params.get('error')
    return code && code in SOCIAL_ERRORS ? t(SOCIAL_ERRORS[code as keyof typeof SOCIAL_ERRORS]) : null
  })

  const schema = useMemo(
    () =>
      z.object({
        email: z.email(t('validation.email')),
        password: z.string().min(1, t('validation.required')),
        remember: z.boolean(),
      }),
    [t],
  )
  type Values = z.infer<typeof schema>

  const { register, handleSubmit, setError, formState } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '', remember: true },
  })

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null)
    try {
      const user = await signIn.mutateAsync(values)
      const from = (location.state as { from?: string } | null)?.from
      navigate(user.onboarded ? (from ?? '/') : '/onboarding', { replace: true })
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['email', 'password']))
    }
  })

  return (
    <>
      <AuthHeading title={t('auth.signInTitle')} subtitle={t('auth.signInSubtitle')} />
      {params.get('reset') === '1' && <FormAlert tone="success">{t('auth.resetDone')}</FormAlert>}
      <form onSubmit={onSubmit} noValidate className="mt-space-md flex flex-col gap-space-md">
        {formError && <FormAlert>{formError}</FormAlert>}
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
          autoComplete="current-password"
          error={formState.errors.password?.message}
          {...register('password')}
        />
        <div className="flex items-center justify-between">
          <Checkbox label={t('auth.remember')} {...register('remember')} />
          <Link to="/forgot-password" className="min-h-tap py-3 text-label-md text-primary hover:underline">
            {t('auth.forgot')}
          </Link>
        </div>
        <Button type="submit" loading={formState.isSubmitting}>
          {t('welcome.signIn')}
        </Button>
      </form>
      <div className="my-space-md flex items-center gap-3 text-label-sm text-outline" aria-hidden="true">
        <span className="h-px flex-1 bg-outline-variant/50" />
        {t('common.or')}
        <span className="h-px flex-1 bg-outline-variant/50" />
      </div>
      <GoogleButton />
      <p className="mt-auto pt-space-lg text-center text-body-sm text-on-surface-variant">
        {t('auth.noAccount')}{' '}
        <Link to="/sign-up" className="font-semibold text-primary hover:underline">
          {t('welcome.createAccount')}
        </Link>
      </p>
    </>
  )
}
