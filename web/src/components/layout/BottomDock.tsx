import type { ReactNode } from 'react'

/** Sticky bottom action dock (onboarding, event detail): full-width glass bar, content aligned to the narrow column. */
export function BottomDock({ children }: { children: ReactNode }) {
  return (
    <div className="fixed inset-x-0 bottom-0 z-50 glass px-space-md pt-3 pb-[max(1.5rem,env(safe-area-inset-bottom))] shadow-dock lg:pb-4">
      <div className="mx-auto flex w-full max-w-narrow items-center justify-between gap-3 lg:px-8">{children}</div>
    </div>
  )
}
