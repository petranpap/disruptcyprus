import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'
import { Icon, type IconName } from './Icon'

interface EmptyStateProps {
  icon: IconName
  title: string
  body?: string
  action?: ReactNode
  tone?: 'neutral' | 'error'
  className?: string
}

/** Empty, error and offline states: tinted 64px icon circle, title, body, one action. */
export function EmptyState({ icon, title, body, action, tone = 'neutral', className }: EmptyStateProps) {
  return (
    <div className={cn('flex flex-col items-center px-space-lg py-space-xl text-center', className)}>
      <div
        className={cn(
          'mb-space-md flex size-16 items-center justify-center rounded-pill',
          tone === 'error' ? 'bg-error-container text-error' : 'bg-primary-fixed text-primary',
        )}
      >
        <Icon name={icon} size={30} />
      </div>
      <h2 className="font-headline text-headline-sm text-on-surface">{title}</h2>
      {body && <p className="mt-space-sm max-w-xs text-body-sm text-on-surface-variant">{body}</p>}
      {action && <div className="mt-space-lg w-full max-w-xs">{action}</div>}
    </div>
  )
}
