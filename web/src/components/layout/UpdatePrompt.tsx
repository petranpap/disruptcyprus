import { useTranslation } from 'react-i18next'
import { useRegisterSW } from 'virtual:pwa-register/react'

/** "New version available" snackbar (service worker waiting); refresh activates it. */
export function UpdatePrompt() {
  const { t } = useTranslation()
  const {
    needRefresh: [needRefresh],
    updateServiceWorker,
  } = useRegisterSW()

  if (!needRefresh) return null

  return (
    <div
      role="status"
      className="fixed inset-x-0 bottom-24 z-[60] mx-auto w-[calc(100%-2rem)] max-w-[448px] lg:right-6 lg:bottom-6 lg:left-auto lg:mx-0"
    >
      <div className="flex items-center justify-between gap-3 rounded-control bg-ink px-space-md py-3 text-white shadow-float">
        <p className="text-body-sm">{t('update.available')}</p>
        <button
          type="button"
          onClick={() => void updateServiceWorker(true)}
          className="min-h-tap px-2 text-label-lg font-bold text-brand-cyan"
        >
          {t('update.refresh')}
        </button>
      </div>
    </div>
  )
}
