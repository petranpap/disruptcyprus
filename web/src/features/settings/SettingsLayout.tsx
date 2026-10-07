import type { ReactNode } from 'react'
import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import { Icon } from '@/components/ui/Icon'

/** Settings sub-page frame: back to Profile on phones, title, narrow column. */
export function SettingsPage({
  title,
  children,
  description,
}: {
  title: string
  description?: string
  children: ReactNode
}) {
  const { t } = useTranslation()

  return (
    <div className="mx-auto w-full max-w-narrow space-y-space-lg">
      <div>
        <Link
          to="/profile"
          className="-ml-1 inline-flex min-h-tap items-center gap-1 text-label-lg text-on-surface-variant hover:text-primary"
        >
          <Icon name="arrow_back" size={18} />
          {t('nav.profile')}
        </Link>
        <h1 className="font-headline text-headline-hero-mobile text-on-surface lg:text-headline-hero">{title}</h1>
        {description && <p className="mt-1 font-body text-body-md text-on-surface-variant">{description}</p>}
      </div>
      {children}
    </div>
  )
}

export function SettingsCard({ children, title }: { children: ReactNode; title?: string }) {
  return (
    <section className="rounded-card border border-card-stroke bg-surface-container-lowest p-space-md">
      {title && <h2 className="mb-2 font-headline text-headline-sm text-on-surface">{title}</h2>}
      {children}
    </section>
  )
}
