import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'
import { upper } from '@/lib/greek'

export type BadgeVariant = 'original' | 'original-soft' | 'category' | 'cyan' | 'tag' | 'neutral'

const VARIANTS: Record<BadgeVariant, string> = {
  /** Crimson "Disrupt Original" on hero images. */
  original: 'rounded-pill bg-secondary-container px-2.5 py-1 text-on-secondary-container shadow-md',
  /** Tinted crimson badge in the reader header. */
  'original-soft': 'rounded-pill bg-secondary-fixed px-2.5 py-1 text-secondary',
  /** Tinted cyan category chip. */
  category: 'rounded-pill bg-primary-fixed px-2.5 py-1 text-on-primary-fixed-variant',
  /** Solid cyan badge on images. */
  cyan: 'rounded-pill bg-brand-cyan px-2.5 py-1 text-white shadow',
  /** Square tag over images (frosted). */
  tag: 'rounded-tag bg-ink/75 px-2 py-0.5 text-white backdrop-blur-md',
  neutral: 'rounded-tag bg-surface-container-high px-2 py-0.5 text-on-surface-variant',
}

interface BadgeProps {
  children: string
  variant?: BadgeVariant
  dot?: boolean
  icon?: ReactNode
  className?: string
}

/** Uppercase, tracked label (11px) — Greek accents are dropped when uppercasing. */
export function Badge({ children, variant = 'category', dot = false, icon, className }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 text-label-md font-bold whitespace-nowrap',
        VARIANTS[variant],
        className,
      )}
    >
      {dot && <span className="size-1.5 rounded-pill bg-current" aria-hidden="true" />}
      {icon}
      {upper(children)}
    </span>
  )
}
