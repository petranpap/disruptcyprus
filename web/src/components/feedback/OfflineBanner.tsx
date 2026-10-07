import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import { Icon } from '@/components/ui/Icon'
import { useOnline } from '@/hooks/useOnline'

export function OfflineBanner() {
  const { t } = useTranslation()
  const online = useOnline()

  if (online) return null

  return (
    <div role="status" className="bg-ink text-white">
      <div className="mx-auto flex w-full max-w-content items-center gap-2 px-space-md py-2 text-body-sm lg:px-8">
        <Icon name="cloud_off" size={18} />
        <span className="flex-1">{t('offline.banner')}</span>
        <Link to="/saved" className="font-semibold text-brand-cyan underline-offset-2 hover:underline">
          {t('offline.openSaved')}
        </Link>
      </div>
    </div>
  )
}
