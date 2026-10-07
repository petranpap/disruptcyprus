import { cn } from '@/lib/cn'

interface SegmentedControlProps<T extends string> {
  value: T
  options: { value: T; label: string }[]
  onChange: (value: T) => void
  label: string
  className?: string
}

/** Pill track with a raised active segment (sub-tabs, language and theme pickers). */
export function SegmentedControl<T extends string>({
  value,
  options,
  onChange,
  label,
  className,
}: SegmentedControlProps<T>) {
  return (
    <div
      role="radiogroup"
      aria-label={label}
      className={cn('flex rounded-pill bg-surface-container-low p-1', className)}
    >
      {options.map((option) => {
        const active = option.value === value

        return (
          <button
            key={option.value}
            type="button"
            role="radio"
            aria-checked={active}
            onClick={() => onChange(option.value)}
            className={cn(
              'min-h-10 flex-1 rounded-pill px-3 text-label-lg transition-colors',
              active
                ? 'bg-surface-container-lowest text-on-surface shadow-card dark:bg-surface-container-highest'
                : 'text-on-surface-variant hover:text-on-surface',
            )}
          >
            {option.label}
          </button>
        )
      })}
    </div>
  )
}
