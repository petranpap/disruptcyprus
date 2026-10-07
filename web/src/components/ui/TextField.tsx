import { useId, useState, type ComponentProps, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/lib/cn'
import { Icon } from './Icon'

interface TextFieldProps extends ComponentProps<'input'> {
  label: string
  hint?: ReactNode
  error?: string
}

export function TextField({ label, hint, error, className, id, ...rest }: TextFieldProps) {
  const generatedId = useId()
  const inputId = id ?? generatedId
  const describedBy = error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined

  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      <label htmlFor={inputId} className="text-label-lg text-on-surface">
        {label}
      </label>
      <input
        id={inputId}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy}
        className={cn(
          'h-12 w-full rounded-control border bg-surface-container-lowest px-space-md text-body-sm text-on-surface placeholder:text-outline',
          'transition-colors outline-none focus:border-brand-cyan focus:ring-2 focus:ring-brand-cyan/25',
          error ? 'border-error' : 'border-outline-variant/70',
        )}
        {...rest}
      />
      {error ? (
        <p id={`${inputId}-error`} className="text-body-sm text-error" role="alert">
          {error}
        </p>
      ) : (
        hint && (
          <p id={`${inputId}-hint`} className="text-body-sm text-outline">
            {hint}
          </p>
        )
      )}
    </div>
  )
}

export function PasswordField(props: Omit<TextFieldProps, 'type'>) {
  const { t } = useTranslation()
  const [visible, setVisible] = useState(false)

  return (
    <div className="relative">
      <TextField {...props} type={visible ? 'text' : 'password'} className={cn(props.className, '[&_input]:pr-12')} />
      <button
        type="button"
        onClick={() => setVisible((value) => !value)}
        aria-label={visible ? t('auth.hidePassword') : t('auth.showPassword')}
        aria-pressed={visible}
        className="absolute top-[26px] right-0 inline-flex size-12 items-center justify-center text-on-surface-variant hover:text-primary"
      >
        <Icon name={visible ? 'visibility_off' : 'visibility'} size={20} />
      </button>
    </div>
  )
}
