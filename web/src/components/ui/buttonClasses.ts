import { cn } from '@/lib/cn'

export type ButtonVariant = 'primary' | 'outline' | 'social' | 'ghost' | 'danger'

const VARIANTS: Record<ButtonVariant, string> = {
  primary: 'bg-primary-action text-on-primary shadow-sm hover:bg-primary-action-hover font-bold',
  outline:
    'bg-surface-container-lowest text-on-surface border border-outline-variant/70 shadow-sm hover:bg-surface-container-low font-semibold',
  social:
    'bg-surface-container-lowest text-on-surface border border-outline-variant/60 shadow-sm hover:bg-surface-container-low font-medium',
  ghost: 'text-on-surface-variant hover:text-primary font-medium',
  danger: 'bg-surface-container-lowest text-error border border-error/60 hover:bg-error-container/40 font-semibold',
}

/** Shared classes so links and buttons look identical (design: 12px radius, py-3.5, press scale). */
export function buttonClasses(variant: ButtonVariant = 'primary', block = true): string {
  return cn(
    'press inline-flex min-h-tap items-center justify-center gap-2 rounded-control px-space-md py-3 text-label-lg transition-colors duration-150',
    'disabled:pointer-events-none disabled:opacity-50',
    block && 'w-full',
    VARIANTS[variant],
  )
}
