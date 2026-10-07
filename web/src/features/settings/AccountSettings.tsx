import { zodResolver } from '@hookform/resolvers/zod'
import { useMemo, useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import {
  useChangePassword,
  useMe,
  useRemoveAvatar,
  useUpdateEmail,
  useUpdateProfile,
  useUploadAvatar,
} from '@/api/auth'
import { Button } from '@/components/ui/Button'
import { Icon } from '@/components/ui/Icon'
import { PasswordField, TextField } from '@/components/ui/TextField'
import { FormAlert } from '@/features/auth/AuthHeading'
import { applyServerErrors } from '@/lib/forms'
import { PASSWORD_RULE } from '@/lib/validation'
import { useUiStore } from '@/stores/ui'
import { SettingsCard, SettingsPage } from './SettingsLayout'

function AvatarCard() {
  const { t } = useTranslation()
  const { data: user } = useMe()
  const upload = useUploadAvatar()
  const remove = useRemoveAvatar()
  const input = useRef<HTMLInputElement>(null)
  const [error, setError] = useState<string | null>(null)

  return (
    <SettingsCard title={t('settings.account.photo')}>
      <div className="flex items-center gap-4">
        <span className="flex size-16 items-center justify-center overflow-hidden rounded-pill bg-primary-fixed text-on-primary-fixed-variant">
          {user?.avatar_url ? (
            <img src={user.avatar_url} alt="" className="size-full object-cover" />
          ) : (
            <Icon name="person" size={30} />
          )}
        </span>
        <div className="flex flex-wrap gap-2">
          <Button block={false} variant="outline" loading={upload.isPending} onClick={() => input.current?.click()}>
            {t('settings.account.upload')}
          </Button>
          {user?.avatar_url && (
            <Button block={false} variant="ghost" loading={remove.isPending} onClick={() => remove.mutate()}>
              {t('settings.account.remove')}
            </Button>
          )}
        </div>
        <input
          ref={input}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          className="sr-only"
          aria-label={t('settings.account.upload')}
          onChange={(event) => {
            const file = event.target.files?.[0]
            if (!file) return
            setError(null)
            upload.mutate(file, {
              onError: (caught) => setError(applyServerErrors(caught, () => undefined, []) ?? t('errors.generic')),
            })
            event.target.value = ''
          }}
        />
      </div>
      {error && (
        <p className="mt-2 text-body-sm text-error" role="alert">
          {error}
        </p>
      )}
    </SettingsCard>
  )
}

function ProfileCard() {
  const { t } = useTranslation()
  const { data: user } = useMe()
  const updateProfile = useUpdateProfile()
  const updateEmail = useUpdateEmail()
  const showToast = useUiStore((state) => state.showToast)
  const [name, setName] = useState(user?.name ?? '')
  const [email, setEmail] = useState(user?.email ?? '')
  const [currentPassword, setCurrentPassword] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})
  const emailChanged = email.trim().toLowerCase() !== user?.email

  const save = async () => {
    setErrors({})
    try {
      if (name.trim() !== user?.name) await updateProfile.mutateAsync({ name: name.trim() })
      if (emailChanged)
        await updateEmail.mutateAsync({
          email: email.trim(),
          ...(user?.has_password ? { current_password: currentPassword } : {}),
        })
      showToast(emailChanged ? t('settings.account.emailChanged') : t('settings.saved'))
      setCurrentPassword('')
    } catch (error) {
      const general = applyServerErrors(
        error,
        (field, { message }) => setErrors((current) => ({ ...current, [field]: message ?? '' })),
        ['name', 'email', 'current_password'],
      )
      if (general) setErrors((current) => ({ ...current, general }))
    }
  }

  return (
    <SettingsCard title={t('settings.account.details')}>
      <div className="flex flex-col gap-space-md">
        {errors.general && <FormAlert>{errors.general}</FormAlert>}
        <TextField
          label={t('auth.name')}
          value={name}
          onChange={(event) => setName(event.target.value)}
          error={errors.name}
          autoComplete="name"
        />
        <TextField
          label={t('auth.email')}
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          error={errors.email}
          autoComplete="email"
          hint={emailChanged ? t('settings.account.emailChangeNote') : undefined}
        />
        {emailChanged && user?.has_password && (
          <PasswordField
            label={t('settings.account.currentPassword')}
            value={currentPassword}
            onChange={(event) => setCurrentPassword(event.target.value)}
            error={errors.current_password}
            autoComplete="current-password"
          />
        )}
        <Button loading={updateProfile.isPending || updateEmail.isPending} onClick={() => void save()}>
          {t('common.save')}
        </Button>
      </div>
    </SettingsCard>
  )
}

function PasswordCard() {
  const { t } = useTranslation()
  const { data: user } = useMe()
  const change = useChangePassword()
  const showToast = useUiStore((state) => state.showToast)
  const [formError, setFormError] = useState<string | null>(null)
  const needsCurrent = user?.has_password ?? true

  const schema = useMemo(
    () =>
      z
        .object({
          current_password: needsCurrent ? z.string().min(1, t('validation.required')) : z.string().optional(),
          password: z.string().regex(PASSWORD_RULE, t('validation.passwordRule')),
          password_confirmation: z.string(),
        })
        .refine((values) => values.password === values.password_confirmation, {
          message: t('auth.passwordsMismatch'),
          path: ['password_confirmation'],
        }),
    [needsCurrent, t],
  )
  type Values = z.infer<typeof schema>
  const { register, handleSubmit, setError, reset, formState } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { current_password: '', password: '', password_confirmation: '' },
  })

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null)
    try {
      await change.mutateAsync(
        needsCurrent ? values : { password: values.password, password_confirmation: values.password_confirmation },
      )
      reset()
      showToast(t('settings.account.passwordChanged'))
    } catch (error) {
      setFormError(applyServerErrors(error, setError, ['current_password', 'password', 'password_confirmation']))
    }
  })

  return (
    <SettingsCard title={needsCurrent ? t('settings.account.changePassword') : t('settings.account.setPassword')}>
      <form onSubmit={onSubmit} noValidate className="flex flex-col gap-space-md">
        {formError && <FormAlert>{formError}</FormAlert>}
        {needsCurrent && (
          <PasswordField
            label={t('settings.account.currentPassword')}
            autoComplete="current-password"
            error={formState.errors.current_password?.message}
            {...register('current_password')}
          />
        )}
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
        <Button type="submit" variant="outline" loading={formState.isSubmitting}>
          {needsCurrent ? t('settings.account.changePassword') : t('settings.account.setPassword')}
        </Button>
      </form>
    </SettingsCard>
  )
}

export function AccountSettings() {
  const { t } = useTranslation()

  return (
    <SettingsPage title={t('settings.account.title')}>
      <AvatarCard />
      <ProfileCard />
      <PasswordCard />
    </SettingsPage>
  )
}
