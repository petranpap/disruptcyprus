import { Outlet } from 'react-router'
import { GuestGateSheet } from '@/components/feedback/GuestGateSheet'
import { OfflineBanner } from '@/components/feedback/OfflineBanner'
import { Toaster } from '@/components/feedback/Toaster'
import { UpdatePrompt } from '@/components/layout/UpdatePrompt'

/** Wraps every route: offline banner, guest sign-up prompt, toasts and the update prompt need the router. */
export function RootLayout() {
  return (
    <>
      <OfflineBanner />
      <Outlet />
      <GuestGateSheet />
      <Toaster />
      <UpdatePrompt />
    </>
  )
}
