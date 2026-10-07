import { useTranslation } from 'react-i18next'
import { useUiStore } from '@/stores/ui'

/** Native share sheet where available; otherwise copies the public share link. */
export function useShare() {
  const { t } = useTranslation()
  const showToast = useUiStore((state) => state.showToast)

  return async ({ title, url }: { title: string; url: string }) => {
    if (navigator.share) {
      try {
        await navigator.share({ title, url })
      } catch {
        // Cancelled by the reader.
      }
      return
    }

    try {
      await navigator.clipboard.writeText(url)
      showToast(t('share.copied'))
    } catch {
      showToast(url)
    }
  }
}
