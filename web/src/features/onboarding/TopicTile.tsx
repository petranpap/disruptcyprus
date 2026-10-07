import { useTranslation } from 'react-i18next'
import type { Industry } from '@/api/schemas'
import { Icon } from '@/components/ui/Icon'
import { cn } from '@/lib/cn'
import { upper } from '@/lib/greek'

interface TopicTileProps {
  industry: Industry
  selected: boolean
  onToggle: (industry: Industry) => void
  /** Show the cluster name on the tile (off when tiles already sit under a cluster heading). */
  showGroup?: boolean
}

/**
 * Onboarding tile (UI/onboarding_topic_selection), shortened to 128px so 33 industries stay scannable.
 * Without an uploaded image, the industry colour forms the background.
 */
export function TopicTile({ industry, selected, onToggle, showGroup = false }: TopicTileProps) {
  const { t } = useTranslation()

  return (
    <button
      type="button"
      aria-pressed={selected}
      onClick={() => onToggle(industry)}
      className={cn(
        'group relative h-32 press overflow-hidden rounded-card text-left select-none',
        selected ? 'shadow-md ring-2 ring-brand-cyan' : 'ring-1 ring-outline-variant/30 hover:ring-outline',
      )}
    >
      {industry.image_url ? (
        <img
          src={industry.image_url}
          alt=""
          loading="lazy"
          className="absolute inset-0 size-full object-cover transition-transform duration-500 motion-safe:group-hover:scale-105"
        />
      ) : (
        <span
          className="absolute inset-0"
          style={{
            backgroundImage: `radial-gradient(circle at 80% 15%, rgb(255 255 255 / 0.22), transparent 45%), linear-gradient(135deg, ${industry.color}, #0F172A)`,
          }}
          aria-hidden="true"
        />
      )}
      <span className="absolute inset-0 scrim-tile" aria-hidden="true" />
      {selected && <span className="absolute inset-0 bg-brand-cyan/15 mix-blend-multiply" aria-hidden="true" />}
      <span
        className={cn(
          'absolute top-2.5 right-2.5 flex size-7 items-center justify-center rounded-pill transition-transform duration-150',
          selected
            ? 'bg-brand-cyan text-white shadow-md'
            : 'border border-white/60 bg-white/20 text-transparent backdrop-blur-sm',
        )}
        aria-hidden="true"
      >
        <Icon name={selected ? 'check-fill' : 'check'} size={16} />
      </span>
      <span className="absolute inset-x-3 bottom-3">
        {showGroup && (
          <span className="mb-0.5 block text-label-sm tracking-wider text-white/80">
            {upper(t(`onboarding.industries.groups.${industry.group}`))}
          </span>
        )}
        <span className="block font-headline text-headline-sm leading-tight text-white">{industry.name}</span>
      </span>
      {selected && <span className="sr-only">{t('onboarding.industries.selected')}</span>}
    </button>
  )
}
