import type { ComponentProps, ReactNode } from 'react'
import { Link, type LinkProps } from 'react-router'
import { cn } from '@/lib/cn'
import { buttonClasses, type ButtonVariant } from './buttonClasses'
import { Spinner } from './Spinner'

interface ButtonProps extends ComponentProps<'button'> {
  variant?: ButtonVariant
  block?: boolean
  loading?: boolean
  icon?: ReactNode
  trailingIcon?: ReactNode
}

export function Button({
  variant = 'primary',
  block = true,
  loading = false,
  icon,
  trailingIcon,
  className,
  children,
  disabled,
  type = 'button',
  ...rest
}: ButtonProps) {
  return (
    <button
      type={type}
      className={cn(buttonClasses(variant, block), className)}
      disabled={disabled || loading}
      aria-busy={loading || undefined}
      {...rest}
    >
      {loading ? <Spinner size={18} /> : icon}
      <span>{children}</span>
      {!loading && trailingIcon}
    </button>
  )
}

interface ButtonLinkProps extends LinkProps {
  variant?: ButtonVariant
  block?: boolean
  icon?: ReactNode
  trailingIcon?: ReactNode
}

export function ButtonLink({
  variant = 'primary',
  block = true,
  icon,
  trailingIcon,
  className,
  children,
  ...rest
}: ButtonLinkProps) {
  return (
    <Link className={cn(buttonClasses(variant, block), className)} {...rest}>
      {icon}
      <span>{children}</span>
      {trailingIcon}
    </Link>
  )
}
