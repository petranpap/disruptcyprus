import type { ComponentProps } from 'react'
import { cn } from '@/lib/cn'

interface ChipProps extends ComponentProps<'button'> {
  selected?: boolean
  color?: string
}

/** Industry filter chip: tinted when selected, optional industry colour dot. */
export function Chip({ selected = false, color, className, children, type = 'button', ...rest }: ChipProps) {
  return (
    <button
      type={type}
      aria-pressed={selected}
      className={cn(
        'inline-flex min-h-9 shrink-0 press items-center gap-1.5 rounded-pill px-3.5 text-label-lg whitespace-nowrap transition-colors',
        selected
          ? 'bg-primary-fixed text-on-primary-fixed-variant ring-1 ring-brand-cyan/40'
          : 'bg-surface-container-lowest text-on-surface-variant hairline hover:text-on-surface',
        className,
      )}
      {...rest}
    >
      {color && <span className="size-2 rounded-pill" style={{ backgroundColor: color }} aria-hidden="true" />}
      {children}
    </button>
  )
}
