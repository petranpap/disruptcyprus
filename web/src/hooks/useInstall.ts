import { useCallback } from 'react'
import { isStandalone } from '@/hooks/useStandalone'
import { currentInstallPlatform } from '@/lib/platform'
import { useEngagementStore } from '@/stores/engagement'
import { useUiStore } from '@/stores/ui'

/**
 * "Install the app": the browser's one-tap prompt where it exists (Chrome/Edge/Samsung), otherwise the
 * step-by-step sheet for the reader's device. Hidden in the installed app.
 */
export function useInstall() {
  const installEvent = useEngagementStore((state) => state.installEvent)
  const setInstallEvent = useEngagementStore((state) => state.setInstallEvent)
  const openInstallSheet = useUiStore((state) => state.openInstallSheet)
  const installed = isStandalone()

  const install = useCallback(async () => {
    if (!installEvent) {
      openInstallSheet()
      return
    }
    await installEvent.prompt()
    const { outcome } = await installEvent.userChoice
    // The prompt can only be used once; after a refusal, the sheet still explains the manual way.
    setInstallEvent(null)
    if (outcome === 'dismissed') openInstallSheet()
  }, [installEvent, openInstallSheet, setInstallEvent])

  return { installed, canPrompt: installEvent !== null, platform: currentInstallPlatform(), install }
}
