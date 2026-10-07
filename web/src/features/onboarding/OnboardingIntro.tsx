import type { IconName } from '@/components/ui/Icon'
import { Icon } from '@/components/ui/Icon'

export function OnboardingIntro({
  icon,
  kicker,
  title,
  subtitle,
}: {
  icon: IconName
  kicker: string
  title: string
  subtitle: string
}) {
  return (
    <section className="mb-space-lg">
      <p className="mb-space-xs inline-flex items-center gap-1.5 rounded-pill bg-primary-fixed px-2.5 py-1 text-label-md text-on-primary-fixed-variant">
        <Icon name={icon} size={14} />
        {kicker}
      </p>
      <h1 className="mt-1 font-headline text-headline-hero-mobile text-on-surface">{title}</h1>
      <p className="mt-2 font-body text-body-md text-on-surface-variant">{subtitle}</p>
    </section>
  )
}
