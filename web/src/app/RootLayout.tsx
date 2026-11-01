import { useEffect } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { Outlet, useNavigate, useSearchParams } from 'react-router'
import { useMe } from '@/api/auth'
import { refreshNotifications } from '@/api/notifications'
import { EngagementPrompts } from '@/components/feedback/EngagementPrompts'
import { GuestGateSheet } from '@/components/feedback/GuestGateSheet'
import { InstallSheet } from '@/components/feedback/InstallSheet'
import { OfflineBanner } from '@/components/feedback/OfflineBanner'
import { Toaster } from '@/components/feedback/Toaster'
import { UpdatePrompt } from '@/components/layout/UpdatePrompt'
import { resyncPush } from '@/lib/push'
import { useUiStore } from '@/stores/ui'

/** Messages from the service worker: a push arrived (refresh the inbox) or a notification asks to navigate. */
function useServiceWorkerMessages() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()

  useEffect(() => {
    if (!('serviceWorker' in navigator)) return
    const onMessage = (event: MessageEvent<{ type?: string; url?: string }>) => {
      if (event.data?.type === 'NOTIFICATIONS_CHANGED') refreshNotifications(queryClient)
      if (event.data?.type === 'NAVIGATE' && event.data.url) {
        const url = new URL(event.data.url, window.location.origin)
        if (url.origin === window.location.origin) navigate(url.pathname + url.search)
      }
    }
    const container = navigator.serviceWorker
    container.addEventListener('message', onMessage)

    return () => container.removeEventListener('message', onMessage)
  }, [queryClient, navigate])
}

/** `?install=1` (the "Install the app" button on the public site) opens the install instructions once. */
function useInstallLink() {
  const [params, setParams] = useSearchParams()
  const openInstallSheet = useUiStore((state) => state.openInstallSheet)
  const wantsInstall = params.get('install') === '1'

  useEffect(() => {
    if (!wantsInstall) return
    openInstallSheet()
    setParams(
      (current) => {
        current.delete('install')
        return current
      },
      { replace: true },
    )
  }, [wantsInstall, openInstallSheet, setParams])
}

/** Wraps every route: offline banner, guest sign-up prompt, toasts and the update prompt need the router. */
export function RootLayout() {
  const { data: user } = useMe()
  const userId = user?.id
  useServiceWorkerMessages()
  useInstallLink()

  // Keep this device's push subscription attached to the signed-in reader (also after the browser rotates it).
  useEffect(() => {
    if (userId) void resyncPush().catch(() => undefined)
  }, [userId])

  return (
    <>
      <OfflineBanner />
      <Outlet />
      <GuestGateSheet />
      <InstallSheet />
      <EngagementPrompts />
      <Toaster />
      <UpdatePrompt />
    </>
  )
}
