import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'

/** Mobile-first app column: full width on phones, centered 480px column with hairline sides on wider screens. */
export function AppColumn({ children, className }: { children: ReactNode; className?: string }) {
  return (
    <div
      className={cn(
        'relative mx-auto min-h-dvh w-full max-w-app-column bg-background md:border-x md:border-hairline',
        className,
      )}
    >
      {children}
    </div>
  )
}
