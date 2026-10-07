import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router'
import { useMe } from '@/api/auth'
import { Logo } from '@/components/ui/Logo'
import { STORAGE_KEYS, storage } from '@/lib/storage'

export function Splash() {
  return (
    <div className="flex min-h-dvh items-center justify-center bg-background" role="status" aria-busy="true">
      <Logo size="lg" />
    </div>
  )
}

/**
 * Main app routes: send readers who have not finished onboarding back to it, and first-time guests on the home page to Welcome.
 */
export function AppGate({ children }: { children: ReactNode }) {
  const { data: user, isPending } = useMe()
  const location = useLocation()

  if (isPending) return <Splash />
  if (user && !user.onboarded) return <Navigate to="/onboarding" replace />
  // Only the home page sends first-time guests to Welcome; shared deep links open directly.
  if (!user && location.pathname === '/' && storage.get(STORAGE_KEYS.welcomed) !== '1')
    return <Navigate to="/welcome" replace />

  return children
}

/** Auth screens are for guests: signed-in readers continue where they belong. */
export function GuestOnly({ children }: { children: ReactNode }) {
  const { data: user, isPending } = useMe()

  if (isPending) return <Splash />
  if (user) return <Navigate to={user.onboarded ? '/' : '/onboarding'} replace />

  return children
}

export function RequireAuth({ children }: { children: ReactNode }) {
  const { data: user, isPending } = useMe()
  const location = useLocation()

  if (isPending) return <Splash />
  if (!user) return <Navigate to="/sign-in" replace state={{ from: location.pathname + location.search }} />

  return children
}
