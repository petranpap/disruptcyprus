import { cn } from '@/lib/cn'

interface LogoProps {
  size?: 'sm' | 'md' | 'lg'
  withWordmark?: boolean
  className?: string
}

const MARK = { sm: 'size-7', md: 'size-9', lg: 'size-12 -rotate-1' }
const WORD = { sm: 'text-headline-md', md: 'text-headline-lg', lg: 'text-[30px] leading-9' }

/** The "#" squircle, identical to the app icon (public/favicon.svg). */
export function LogoMark({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 512 512" className={className} aria-hidden="true" focusable="false">
      <rect width="512" height="512" rx="112" className="fill-brand-cyan" />
      <g fill="#FFFFFF" transform="skewX(-12) translate(54 0)">
        <rect x="172" y="104" width="56" height="304" rx="16" />
        <rect x="284" y="104" width="56" height="304" rx="16" />
        <rect x="112" y="186" width="288" height="56" rx="16" />
        <rect x="112" y="270" width="288" height="56" rx="16" />
      </g>
    </svg>
  )
}

/**
 * Brand mark as drawn in the Stitch screens: "#" squircle + Playfair wordmark ("Disrupt" crimson, "Cyprus" cyan).
 * Replace with the SVG logo once supplied.
 */
export function Logo({ size = 'sm', withWordmark = true, className }: LogoProps) {
  return (
    <span className={cn('inline-flex items-center gap-2', className)} aria-label="Disrupt Cyprus" role="img">
      <LogoMark className={cn('shrink-0 drop-shadow-sm', MARK[size])} />
      {withWordmark && (
        <span className={cn('font-headline font-bold tracking-tight', WORD[size])} aria-hidden="true">
          <span className="text-brand-crimson">Disrupt</span>
          <span className="text-brand-cyan">Cyprus</span>
        </span>
      )}
    </span>
  )
}
