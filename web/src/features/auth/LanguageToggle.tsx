import { useTranslation } from 'react-i18next'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { useLocale } from '@/hooks/useLocale'

export function LanguageToggle({ className }: { className?: string }) {
  const { t } = useTranslation()
  const { locale, setLocale } = useLocale()

  return (
    <SegmentedControl
      className={className}
      label={t('language.label')}
      value={locale}
      onChange={(next) => void setLocale(next)}
      options={[
        { value: 'el', label: t('language.short_el') },
        { value: 'en', label: t('language.short_en') },
      ]}
    />
  )
}
