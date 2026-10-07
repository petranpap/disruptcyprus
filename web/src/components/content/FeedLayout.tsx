import type { ReactNode } from 'react'

/** Main column plus a 320px sidebar on desktop; the sidebar is hidden on phones (its content appears inline). */
export function FeedLayout({ children, sidebar }: { children: ReactNode; sidebar?: ReactNode }) {
  if (!sidebar) return <>{children}</>

  return (
    <div className="lg:grid lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start lg:gap-10">
      <div className="min-w-0">{children}</div>
      <aside className="hidden space-y-space-xl lg:sticky lg:top-24 lg:block">{sidebar}</aside>
    </div>
  )
}
