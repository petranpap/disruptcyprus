import { useTranslation } from 'react-i18next'
import { ThemeToggle } from '@/features/profile/ThemeToggle'
import { SettingsCard, SettingsPage } from './SettingsLayout'

export function AppearanceSettings() {
  const { t } = useTranslation()

  return (
    <SettingsPage title={t('settings.appearance.title')}>
      <SettingsCard>
        <ThemeToggle />
        <p className="mt-3 text-body-sm text-outline">{t('settings.appearance.hint')}</p>
      </SettingsCard>
    </SettingsPage>
  )
}
