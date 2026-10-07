import { useTranslation } from 'react-i18next'
import { useMe } from '@/api/auth'
import { useToggleBookmark, type BookmarkTarget } from '@/api/content'
import { useUiStore } from '@/stores/ui'

/** Save/unsave with the guest prompt for signed-out readers and a confirmation toast. */
export function useBookmark() {
  const { t } = useTranslation()
  const { data: user } = useMe()
  const toggle = useToggleBookmark()
  const openGuestGate = useUiStore((state) => state.openGuestGate)
  const showToast = useUiStore((state) => state.showToast)

  return (target: BookmarkTarget) => {
    if (!user) {
      openGuestGate('save')
      return
    }

    toggle.mutate(target, {
      onSuccess: () => showToast(target.is_bookmarked ? t('saved.removed') : t('saved.added')),
      onError: () => showToast(t('errors.generic')),
    })
  }
}
