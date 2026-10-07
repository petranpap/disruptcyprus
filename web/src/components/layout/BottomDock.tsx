import type { ReactNode } from 'react'

/** Sticky bottom action dock (onboarding, event detail). */
export function BottomDock({ children }: { children: ReactNode }) {
  return (
    <div className="fixed inset-x-0 bottom-0 z-50 mx-auto max-w-app-column glass px-space-md pt-3 pb-[max(1.5rem,env(safe-area-inset-bottom))] shadow-dock">
      <div className="flex items-center justify-between gap-3">{children}</div>
    </div>
  )
}
