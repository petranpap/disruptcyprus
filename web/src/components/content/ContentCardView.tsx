import type { ContentCard } from '@/api/schemas'
import { CompactCard } from './CompactCard'
import { EventRow } from './EventCards'
import { HeroCard } from './HeroCard'
import { StandardCard } from './StandardCard'

export type CardVariant = 'hero' | 'standard' | 'compact'

/** Renders an article in the requested variant; events always use the event row. */
export function ContentCardView({ item, variant }: { item: ContentCard; variant: CardVariant }) {
  if (item.type === 'event') return <EventRow event={item} />
  if (variant === 'hero') return <HeroCard article={item} />
  if (variant === 'standard') return <StandardCard article={item} />

  return <CompactCard article={item} />
}
