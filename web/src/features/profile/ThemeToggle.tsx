import { useTranslation } from 'react-i18next'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { useThemeStore, type ThemePreference } from '@/stores/theme'

export function ThemeToggle() {
  const { t } = useTranslation()
  const preference = useThemeStore((state) => state.preference)
  const setPreference = useThemeStore((state) => state.setPreference)

  return (
    <SegmentedControl<ThemePreference>
      label={t('theme.label')}
      value={preference}
      onChange={setPreference}
      options={[
        { value: 'system', label: t('theme.system') },
        { value: 'light', label: t('theme.light') },
        { value: 'dark', label: t('theme.dark') },
      ]}
    />
  )
}
