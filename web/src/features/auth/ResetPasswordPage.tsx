import { zodResolver } from '@hookform/resolvers/zod'
import { useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useSearchParams } from 'react-router'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { useResetPassword } from '@/api/auth'
import { Button } from '@/components/ui/Button'
import { PasswordField } from '@/components/ui/TextField'
import { applyServerErrors } from '@/lib/forms'
import { PASSWORD_RULE } from '@/lib/validation'
import { AuthHeading, FormAlert } from './AuthHeading'

export function ResetPasswordPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const reset = useResetPassword()
  const [formError, setFormError] = useState<string | null>(null)
  const token = params.get('token') ?? ''
  const email = params.get('email') ?? ''

  const schema = useMemo(
    () =>
      z
        .object({
          password: z.string().regex(PASSWORD_RULE, t('validation.passwordRule')),
          password_confirmation: z.string(),
        })
        .refine((values) => values.password === values.password_confirmation, {
          message: t('auth.passwordsMismatch'),
          path: ['password_confirmation'],
        }),
    [t],
  )
  type Values = z.infer<typeof schema>
  const { register, handleSubmit, setError, formState } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { password: '', password_confirmation: '' },
  })

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null)
    try {
      await reset.mutateAsync({ ...values, token, email })
      navigate('/sign-in?reset=1', { replace: true })
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['password', 'password_confirmation']))
    }
  })

  if (!token || !email) {
    return (
      <>
        <AuthHeading title={t('auth.resetTitle')} />
        <FormAlert>{t('auth.resetInvalidLink')}</FormAlert>
        <Link
          to="/forgot-password"
          className="mt-space-lg min-h-tap text-center text-label-lg text-primary hover:underline"
        >
          {t('auth.sendLink')}
        </Link>
      </>
    )
  }

  return (
    <>
      <AuthHeading title={t('auth.resetTitle')} subtitle={email} />
      <form onSubmit={onSubmit} noValidate className="flex flex-col gap-space-md">
        {formError && <FormAlert>{formError}</FormAlert>}
        <PasswordField
          label={t('auth.newPassword')}
          autoComplete="new-password"
          hint={t('auth.passwordHint')}
          error={formState.errors.password?.message}
          {...register('password')}
        />
        <PasswordField
          label={t('auth.confirmPassword')}
          autoComplete="new-password"
          error={formState.errors.password_confirmation?.message}
          {...register('password_confirmation')}
        />
        <Button type="submit" loading={formState.isSubmitting}>
          {t('common.save')}
        </Button>
      </form>
    </>
  )
}
