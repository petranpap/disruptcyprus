import { useId, type ComponentProps, type ReactNode } from 'react'
import { cn } from '@/lib/cn'

interface CheckboxProps extends Omit<ComponentProps<'input'>, 'type'> {
  label: ReactNode
  error?: string
}

export function Checkbox({ label, error, className, id, ...rest }: CheckboxProps) {
  const generatedId = useId()
  const inputId = id ?? generatedId

  return (
    <div className={cn('flex flex-col gap-1', className)}>
      <label
        htmlFor={inputId}
        className="flex min-h-tap cursor-pointer items-start gap-3 py-2 text-body-sm text-on-surface-variant"
      >
        <input
          id={inputId}
          type="checkbox"
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? `${inputId}-error` : undefined}
          className="mt-0.5 size-5 shrink-0 cursor-pointer rounded accent-primary-action"
          {...rest}
        />
        <span>{label}</span>
      </label>
      {error && (
        <p id={`${inputId}-error`} className="text-body-sm text-error" role="alert">
          {error}
        </p>
      )}
    </div>
  )
}
