import type { ComponentProps } from 'react'
import { cn } from '@/lib/cn'
import { Icon, type IconName } from './Icon'

interface IconButtonProps extends ComponentProps<'button'> {
  icon: IconName
  label: string
  size?: number
  active?: boolean
}

/** 44×44 tap target around a smaller glyph (design icons are 18–24px). */
export function IconButton({
  icon,
  label,
  size = 22,
  active = false,
  className,
  type = 'button',
  children,
  ...rest
}: IconButtonProps) {
  return (
    <button
      type={type}
      aria-label={label}
      className={cn(
        'relative inline-flex size-tap shrink-0 items-center justify-center rounded-pill transition-[color,transform] duration-150 active:scale-95',
        active ? 'text-primary' : 'text-on-surface-variant hover:text-primary',
        className,
      )}
      {...rest}
    >
      <Icon name={icon} size={size} />
      {children}
    </button>
  )
}
