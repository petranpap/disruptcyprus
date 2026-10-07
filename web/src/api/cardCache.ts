import type { QueryClient } from '@tanstack/react-query'

export type CardType = 'article' | 'event'

/**
 * Walks every cached query and patches cards (objects with matching `type` and `id`) wherever they appear:
 * feeds, infinite pages, digests, search results, article detail. Used for optimistic bookmark toggles.
 */
export function patchCards(queryClient: QueryClient, type: CardType, id: number, patch: Record<string, unknown>): void {
  const visit = (value: unknown): unknown => {
    if (Array.isArray(value)) {
      let changed = false
      const next = value.map((item) => {
        const updated = visit(item)
        if (updated !== item) changed = true
        return updated
      })
      return changed ? next : value
    }

    if (value !== null && typeof value === 'object') {
      const record = value as Record<string, unknown>
      let changed = false
      const next: Record<string, unknown> = {}

      for (const [key, child] of Object.entries(record)) {
        const updated = visit(child)
        if (updated !== child) changed = true
        next[key] = updated
      }

      if (record.type === type && record.id === id) {
        return { ...next, ...patch }
      }

      return changed ? next : value
    }

    return value
  }

  for (const query of queryClient.getQueryCache().getAll()) {
    const data = query.state.data
    if (data === undefined) continue

    const updated = visit(data)
    if (updated !== data) queryClient.setQueryData(query.queryKey, updated)
  }
}
