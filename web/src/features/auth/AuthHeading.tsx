export function AuthHeading({ title, subtitle }: { title: string; subtitle?: string }) {
  return (
    <div className="mt-space-lg mb-space-lg">
      <h1 className="font-headline text-headline-hero-mobile text-on-surface">{title}</h1>
      {subtitle && <p className="mt-space-sm font-body text-body-md text-on-surface-variant">{subtitle}</p>}
    </div>
  )
}

export function FormAlert({ tone = 'error', children }: { tone?: 'error' | 'success'; children: string }) {
  return (
    <p
      role={tone === 'error' ? 'alert' : 'status'}
      className={
        tone === 'error'
          ? 'rounded-control bg-error-container px-space-md py-3 text-body-sm text-error'
          : 'rounded-control bg-primary-fixed px-space-md py-3 text-body-sm text-on-primary-fixed-variant'
      }
    >
      {children}
    </p>
  )
}
