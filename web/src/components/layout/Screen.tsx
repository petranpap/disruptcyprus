import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'

/** Full-viewport page surface. Width constraints belong to the page content (Container), not the screen. */
export function Screen({ children, className }: { children: ReactNode; className?: string }) {
  return <div className={cn('relative min-h-dvh w-full bg-background', className)}>{children}</div>
}

/**
 * Responsive content width: full-bleed with a 16px gutter on phones, centred up to 1200px on desktop.
 * `narrow` (768px) for forms and onboarding, `reading` (720px) for the article reader.
 */
export function Container({
  children,
  className,
  width = 'content',
}: {
  children: ReactNode
  className?: string
  width?: 'content' | 'narrow' | 'reading'
}) {
  const max = { content: 'max-w-content', narrow: 'max-w-narrow', reading: 'max-w-reading' }[width]

  return <div className={cn('mx-auto w-full px-margin lg:px-8', max, className)}>{children}</div>
}
