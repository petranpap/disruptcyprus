import { useState } from 'react'
import { useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useDeleteAccount, useMe, useRequestDataExport } from '@/api/auth'
import { clearPersonalCaches } from '@/api/offline'
import { Button } from '@/components/ui/Button'
import { Sheet } from '@/components/ui/Sheet'
import { PasswordField, TextField } from '@/components/ui/TextField'
import { FormAlert } from '@/features/auth/AuthHeading'
import { applyServerErrors } from '@/lib/forms'
import { SITE_URL } from '@/lib/env'
import { useUiStore } from '@/stores/ui'
import { SettingsCard, SettingsPage } from './SettingsLayout'

const CONFIRMATION_WORD = 'DELETE'

/** GDPR: data export and account deletion, plus links to the policies. */
export function PrivacySettings() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { data: user } = useMe()
  const exportData = useRequestDataExport()
  const deleteAccount = useDeleteAccount()
  const showToast = useUiStore((state) => state.showToast)
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [secret, setSecret] = useState('')
  const [error, setError] = useState<string | null>(null)
  const usesPassword = user?.has_password ?? true

  const confirmDelete = async () => {
    setError(null)
    try {
      await deleteAccount.mutateAsync(usesPassword ? { password: secret } : { confirmation: secret })
      await clearPersonalCaches()
      setConfirmOpen(false)
      navigate('/welcome', { replace: true })
      showToast(t('settings.privacy.deleted'))
    } catch (caught) {
      const general = applyServerErrors(caught, (_field, { message }) => setError(message ?? null), [
        'password',
        'confirmation',
      ])
      if (general) setError(general)
    }
  }

  return (
    <SettingsPage title={t('settings.privacy.title')}>
      <SettingsCard title={t('settings.privacy.exportTitle')}>
        <p className="mb-space-md text-body-sm text-on-surface-variant">{t('settings.privacy.exportBody')}</p>
        {exportData.isSuccess ? (
          <FormAlert tone="success">{t('settings.privacy.exportQueued')}</FormAlert>
        ) : (
          <Button
            variant="outline"
            loading={exportData.isPending}
            onClick={() =>
              exportData.mutate(undefined, {
                onError: (caught) => showToast(applyServerErrors(caught, () => undefined, []) ?? t('errors.generic')),
              })
            }
          >
            {t('settings.privacy.exportCta')}
          </Button>
        )}
      </SettingsCard>

      <SettingsCard title={t('settings.privacy.policies')}>
        <ul className="space-y-2 text-body-sm">
          <li>
            <a href={`${SITE_URL}/privacy`} className="text-primary underline underline-offset-2">
              {t('footer.privacy')}
            </a>
          </li>
          <li>
            <a href={`${SITE_URL}/terms`} className="text-primary underline underline-offset-2">
              {t('footer.terms')}
            </a>
          </li>
        </ul>
      </SettingsCard>

      <section className="rounded-card border border-error/40 bg-surface-container-lowest p-space-md">
        <h2 className="mb-2 font-headline text-headline-sm text-error">{t('settings.privacy.deleteTitle')}</h2>
        <p className="mb-space-md text-body-sm text-on-surface-variant">{t('settings.privacy.deleteBody')}</p>
        <Button variant="danger" onClick={() => setConfirmOpen(true)}>
          {t('settings.privacy.deleteCta')}
        </Button>
      </section>

      <Sheet open={confirmOpen} onClose={() => setConfirmOpen(false)} title={t('settings.privacy.deleteConfirmTitle')}>
        <div className="flex flex-col gap-space-md">
          <p className="font-body text-body-md text-on-surface-variant">{t('settings.privacy.deleteBody')}</p>
          {error && <FormAlert>{error}</FormAlert>}
          {usesPassword ? (
            <PasswordField
              label={t('settings.privacy.deleteConfirmPassword')}
              value={secret}
              onChange={(event) => setSecret(event.target.value)}
              autoComplete="current-password"
            />
          ) : (
            <TextField
              label={t('settings.privacy.deleteConfirmWord', { word: CONFIRMATION_WORD })}
              value={secret}
              onChange={(event) => setSecret(event.target.value)}
              autoComplete="off"
            />
          )}
          <Button
            variant="danger"
            loading={deleteAccount.isPending}
            disabled={!secret}
            onClick={() => void confirmDelete()}
          >
            {t('settings.privacy.deleteFinal')}
          </Button>
        </div>
      </Sheet>
    </SettingsPage>
  )
}
