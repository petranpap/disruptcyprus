import { useQuery } from '@tanstack/react-query'
import { api } from './client'
import { articleCardSchema, cursorPageSchema } from './schemas'

/** Trending articles (used by the Welcome preview card; the full feeds arrive in Phase 5). */
export function useTrending(locale: string) {
  return useQuery({
    queryKey: ['feed', 'trending', locale],
    queryFn: async () => (await api.get('/feed/trending', { schema: cursorPageSchema(articleCardSchema) })).data,
  })
}
