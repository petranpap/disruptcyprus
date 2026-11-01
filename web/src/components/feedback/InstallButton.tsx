import { useTranslation } from 'react-i18next'
import { Icon } from '@/components/ui/Icon'
import { useInstall } from '@/hooks/useInstall'
import { cn } from '@/lib/cn'

/**
 * "Install app" icon in the desktop header (the bar has no room for a label next to the account buttons):
 * one tap where the browser allows it, otherwise the instructions sheet.
 */
export function InstallButton({ className }: { className?: string }) {
  const { t } = useTranslation()
  const { installed, install } = useInstall()

  if (installed) return null

  return (
    <button
      type="button"
      onClick={() => void install()}
      aria-label={t('install.button')}
      title={t('install.button')}
      className={cn(
        'size-tap items-center justify-center rounded-pill text-on-surface-variant transition-colors hover:text-primary',
        className,
      )}
    >
      <Icon name="download" size={22} />
    </button>
  )
}
