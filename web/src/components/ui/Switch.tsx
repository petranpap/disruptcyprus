import { useId, type ReactNode } from 'react'
import { cn } from '@/lib/cn'

interface SwitchProps {
  checked: boolean
  onChange: (checked: boolean) => void
  label: ReactNode
  hint?: ReactNode
  disabled?: boolean
}

/** List-row toggle (44×26 switch, cyan when on). */
export function Switch({ checked, onChange, label, hint, disabled = false }: SwitchProps) {
  const id = useId()

  return (
    <div className="flex min-h-tap items-center justify-between gap-4 py-3">
      <div className="min-w-0">
        <p id={`${id}-label`} className="text-label-lg text-on-surface">
          {label}
        </p>
        {hint && <p className="mt-0.5 text-body-sm text-on-surface-variant">{hint}</p>}
      </div>
      <button
        type="button"
        role="switch"
        aria-checked={checked}
        aria-labelledby={`${id}-label`}
        disabled={disabled}
        onClick={() => onChange(!checked)}
        className={cn(
          'relative inline-flex h-[26px] w-11 shrink-0 items-center rounded-pill transition-colors duration-150 disabled:opacity-50',
          checked ? 'bg-primary-action' : 'bg-outline-variant',
        )}
      >
        <span
          className={cn(
            'inline-block size-5 rounded-pill bg-white shadow transition-transform duration-150',
            checked ? 'translate-x-[21px]' : 'translate-x-[3px]',
          )}
          aria-hidden="true"
        />
      </button>
    </div>
  )
}
