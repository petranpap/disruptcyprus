import type { EventCard } from '@/api/schemas'

/** "Venue, City" or the localized "Online" label. */
export function eventWhere(
  event: Pick<EventCard, 'is_online' | 'city' | 'location_name'>,
  onlineLabel: string,
): string {
  return event.is_online ? onlineLabel : [event.location_name, event.city].filter(Boolean).join(', ')
}
