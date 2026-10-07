import { zodResolver } from '@hookform/resolvers/zod'
import { useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { useForgotPassword } from '@/api/auth'
import { Button } from '@/components/ui/Button'
import { TextField } from '@/components/ui/TextField'
import { applyServerErrors } from '@/lib/forms'
import { AuthHeading, FormAlert } from './AuthHeading'

export function ForgotPasswordPage() {
  const { t } = useTranslation()
  const forgot = useForgotPassword()
  const [formError, setFormError] = useState<string | null>(null)

  const schema = useMemo(() => z.object({ email: z.email(t('validation.email')) }), [t])
  type Values = z.infer<typeof schema>
  const { register, handleSubmit, setError, formState } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { email: '' },
  })

  const onSubmit = handleSubmit(async ({ email }) => {
    setFormError(null)
    try {
      await forgot.mutateAsync(email)
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['email']))
    }
  })

  return (
    <>
      <AuthHeading title={t('auth.forgotTitle')} subtitle={t('auth.forgotSubtitle')} />
      {forgot.isSuccess ? (
        <FormAlert tone="success">{t('auth.linkSent')}</FormAlert>
      ) : (
        <form onSubmit={onSubmit} noValidate className="flex flex-col gap-space-md">
          {formError && <FormAlert>{formError}</FormAlert>}
          <TextField
            label={t('auth.email')}
            type="email"
            autoComplete="email"
            inputMode="email"
            error={formState.errors.email?.message}
            {...register('email')}
          />
          <Button type="submit" loading={formState.isSubmitting}>
            {t('auth.sendLink')}
          </Button>
        </form>
      )}
      <Link to="/sign-in" className="mt-space-lg min-h-tap text-center text-label-lg text-primary hover:underline">
        {t('auth.backToSignIn')}
      </Link>
    </>
  )
}
