import { useRef, useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Icon } from '@/components/ui/Icon'
import { cn } from '@/lib/cn'

const THRESHOLD = 70

/**
 * Touch pull-to-refresh for feeds (phones). Shows the design's "Updated just now" pill after refreshing.
 * Mouse and keyboard users refresh by reloading or via the section tabs.
 */
export function PullToRefresh({ onRefresh, children }: { onRefresh: () => Promise<unknown>; children: ReactNode }) {
  const { t } = useTranslation()
  const start = useRef<number | null>(null)
  const [pull, setPull] = useState(0)
  const [state, setState] = useState<'idle' | 'refreshing' | 'done'>('idle')

  const reset = () => {
    start.current = null
    setPull(0)
  }

  return (
    <div
      onTouchStart={(event) => {
        if (window.scrollY <= 0 && state !== 'refreshing') start.current = event.touches[0]?.clientY ?? null
      }}
      onTouchMove={(event) => {
        if (start.current === null) return
        const delta = (event.touches[0]?.clientY ?? 0) - start.current
        setPull(delta > 0 ? Math.min(delta * 0.5, THRESHOLD * 1.4) : 0)
      }}
      onTouchEnd={() => {
        if (pull >= THRESHOLD) {
          setState('refreshing')
          void onRefresh().finally(() => {
            setState('done')
            window.setTimeout(() => setState('idle'), 2500)
          })
        }
        reset()
      }}
    >
      <div
        className={cn(
          'flex justify-center overflow-hidden transition-[height] duration-150',
          pull > 0 || state !== 'idle' ? 'h-9' : 'h-0',
        )}
        style={pull > 0 ? { height: pull * 0.6 } : undefined}
        aria-hidden={state === 'idle'}
      >
        <span className="flex h-7 items-center gap-1.5 self-center rounded-pill border border-card-stroke bg-surface-container-lowest px-3 text-label-sm text-outline">
          <Icon
            name="refresh"
            size={13}
            className={cn('text-brand-cyan', state === 'refreshing' && 'animate-spin')}
            style={pull > 0 ? { transform: `rotate(${pull * 3}deg)` } : undefined}
          />
          {state === 'done'
            ? t('feed.updated')
            : state === 'refreshing'
              ? t('common.loading')
              : t('feed.pullToRefresh')}
        </span>
      </div>
      {children}
    </div>
  )
}
