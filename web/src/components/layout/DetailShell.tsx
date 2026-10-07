import type { ReactNode } from 'react'
import { useMe } from '@/api/auth'
import { AppGate } from '@/app/guards'
import { useSectionTabs } from '@/hooks/useSectionTabs'
import { Screen } from './Screen'
import { SiteFooter } from './SiteFooter'
import { SiteHeader } from './SiteHeader'

/**
 * Article and event pages. Phones follow the reader design: its own sticky toolbar, no bottom navigation.
 * Desktop keeps the website header (toolbar sticks beneath it) and footer.
 */
export function DetailShell({ toolbar, children }: { toolbar: ReactNode; children: ReactNode }) {
  const { data: user } = useMe()
  const tabs = useSectionTabs()

  return (
    <AppGate>
      <Screen className="flex flex-col">
        <div className="hidden lg:block">
          <SiteHeader tabs={tabs} user={user} />
        </div>
        {toolbar}
        <main id="main" className="flex-1 pb-28 lg:pb-space-xl">
          {children}
        </main>
        <SiteFooter tabs={tabs} />
      </Screen>
    </AppGate>
  )
}
