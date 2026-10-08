import { useInfiniteQuery, useMutation, useQuery, useQueryClient, type InfiniteData } from '@tanstack/react-query'
import { z } from 'zod'
import { buildUrl, api } from './client'
import { patchCards, type CardType } from './cardCache'
import { SAVED_CACHE } from './offline'
import { useEngagementStore } from '@/stores/engagement'
import {
  articleCardSchema,
  articleSchema,
  bookmarkedCardSchema,
  calendarSchema,
  contentCardSchema,
  cursorPageSchema,
  dataSchema,
  digestCardSchema,
  digestSchema,
  eventCardSchema,
  eventSchema,
  industrySchema,
  searchResultsSchema,
  type Article,
  type ContentCard,
  type DigestCadence,
  type DigestKind,
} from './schemas'

type Page<T> = { data: T[]; meta: { next_cursor: string | null } & Record<string, unknown> }

/** Shared infinite-query plumbing for cursor-paginated lists. */
export function useCursorList<T>(
  key: readonly unknown[],
  fetchPage: (cursor: string | null) => Promise<Page<T>>,
  enabled = true,
) {
  const query = useInfiniteQuery({
    queryKey: key,
    queryFn: ({ pageParam }) => fetchPage(pageParam),
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => lastPage.meta.next_cursor,
    enabled,
  })

  const pages = (query.data as InfiniteData<Page<T>> | undefined)?.pages ?? []

  return { ...query, items: pages.flatMap((page) => page.data), firstPage: pages[0] }
}

const feedPage = cursorPageSchema(contentCardSchema)

export function useTrending(locale: string) {
  return useQuery({
    queryKey: ['feed', 'trending', locale],
    queryFn: async () => (await api.get('/feed/trending', { schema: cursorPageSchema(articleCardSchema) })).data,
  })
}

export function useForYouFeed(locale: string, enabled: boolean) {
  return useCursorList(
    ['feed', 'for-you', locale],
    (cursor) => api.get('/feed/for-you', { query: { cursor }, schema: feedPage }) as Promise<Page<ContentCard>>,
    enabled,
  )
}

export function useSectionArticles(section: string, industries: string[], locale: string) {
  return useCursorList(['feed', 'section', section, industries, locale], (cursor) =>
    api.get(`/sections/${section}/articles`, {
      query: { cursor, industry: industries.length ? industries.join(',') : undefined },
      schema: cursorPageSchema(articleCardSchema),
    }),
  )
}

export function useIndustryFeed(slug: string, locale: string) {
  return useCursorList(
    ['feed', 'industry', slug, locale],
    (cursor) =>
      api.get(`/industries/${slug}/feed`, { query: { cursor }, schema: feedPage }) as Promise<Page<ContentCard>>,
  )
}

export function useArticle(slug: string, locale: string) {
  return useQuery({
    queryKey: ['article', slug, locale],
    queryFn: async () => (await api.get(`/articles/${slug}`, { schema: dataSchema(articleSchema) })).data,
  })
}

export function useRelatedArticles(slug: string, locale: string) {
  return useQuery({
    queryKey: ['article', slug, 'related', locale],
    queryFn: async () =>
      (await api.get(`/articles/${slug}/related`, { schema: dataSchema(z.array(articleCardSchema)) })).data,
  })
}

/** Fire-and-forget read counter (deduplicated server-side). */
export function recordArticleView(id: number): void {
  useEngagementStore.getState().recordRead()
  void api.post(`/articles/${id}/view`).catch(() => undefined)
}

export type EventRange = 'upcoming' | 'week' | 'month'

export interface EventFilters {
  range: EventRange
  industries: string[]
  online?: boolean
}

const eventsPage = z.object({
  data: z.array(eventCardSchema),
  meta: z
    .object({
      next_cursor: z.string().nullable(),
      digest: z.object({ slug: z.string(), title: z.string(), intro: z.string().nullable() }).nullable().optional(),
    })
    .loose(),
})

export function useEvents(filters: EventFilters, locale: string) {
  return useCursorList(['events', filters, locale], (cursor) =>
    api.get('/events', {
      query: {
        cursor,
        range: filters.range,
        industry: filters.industries.length ? filters.industries.join(',') : undefined,
        online: filters.online ? 1 : undefined,
      },
      schema: eventsPage,
    }),
  )
}

export function useEventCalendar(month: string, locale: string) {
  return useQuery({
    queryKey: ['events', 'calendar', month, locale],
    queryFn: async () =>
      (await api.get('/events/calendar', { query: { month }, schema: dataSchema(calendarSchema) })).data,
  })
}

export function useEvent(slug: string, locale: string) {
  return useQuery({
    queryKey: ['event', slug, locale],
    queryFn: async () => (await api.get(`/events/${slug}`, { schema: dataSchema(eventSchema) })).data,
  })
}

export function useDigests(kind: DigestKind, cadence: DigestCadence, locale: string) {
  return useCursorList(['digests', kind, cadence, locale], (cursor) =>
    api.get('/digests', { query: { kind, cadence, cursor }, schema: cursorPageSchema(digestCardSchema) }),
  )
}

export function useLatestDigest(kind: DigestKind, cadence: DigestCadence, locale: string) {
  return useQuery({
    queryKey: ['digest', 'latest', kind, cadence, locale],
    queryFn: async () =>
      (await api.get('/digests/latest', { query: { kind, cadence }, schema: dataSchema(digestSchema) })).data,
  })
}

export function useDigest(slug: string, locale: string) {
  return useQuery({
    queryKey: ['digest', slug, locale],
    queryFn: async () => (await api.get(`/digests/${slug}`, { schema: dataSchema(digestSchema) })).data,
  })
}

export function useSearch(query: string, locale: string) {
  return useQuery({
    queryKey: ['search', query, locale],
    queryFn: async () =>
      (await api.get('/search', { query: { q: query }, schema: dataSchema(searchResultsSchema) })).data,
    enabled: query.trim().length >= 2,
    placeholderData: (previous) => previous,
  })
}

export function useIndustryList(locale: string) {
  return useQuery({
    queryKey: ['industries', locale],
    queryFn: async () => (await api.get('/industries', { schema: dataSchema(z.array(industrySchema)) })).data,
    staleTime: 60 * 60_000,
  })
}

export function useBookmarks(type: CardType, locale: string, enabled = true) {
  return useCursorList(
    ['bookmarks', type, locale],
    (cursor) => api.get('/bookmarks', { query: { type, cursor }, schema: cursorPageSchema(bookmarkedCardSchema) }),
    enabled,
  )
}

/** Detail endpoint for an article, as the service worker and offline cache see it. */
export const articleApiUrl = (slug: string) => buildUrl(`/articles/${slug}`)

/** Keeps saved articles (JSON + hero image) available offline; removes them when unsaved. */
async function syncOfflineCopy(
  card: { type: CardType; slug: string },
  saved: boolean,
  article?: Pick<Article, 'image'> | null,
): Promise<void> {
  if (card.type !== 'article' || !('caches' in window)) return

  try {
    const cache = await caches.open(SAVED_CACHE)
    const urls = [articleApiUrl(card.slug), ...(article?.image ? [article.image.hero] : [])]

    if (saved) await cache.addAll(urls)
    else await Promise.all(urls.map((url) => cache.delete(url, { ignoreVary: true })))
  } catch {
    // Offline copies are best-effort.
  }
}

export interface BookmarkTarget {
  type: CardType
  id: number
  slug: string
  is_bookmarked: boolean
  image?: { hero: string } | null
}

/** Optimistic save/unsave: every visible card flips immediately and rolls back on failure. */
export function useToggleBookmark() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (target: BookmarkTarget) => {
      const body = { type: target.type, id: target.id }
      if (target.is_bookmarked) await api.delete('/bookmarks', body)
      else await api.post('/bookmarks', body)
    },
    onMutate: (target) => patchCards(queryClient, target.type, target.id, { is_bookmarked: !target.is_bookmarked }),
    onError: (_error, target) =>
      patchCards(queryClient, target.type, target.id, { is_bookmarked: target.is_bookmarked }),
    onSuccess: (_data, target) => {
      if (!target.is_bookmarked) useEngagementStore.getState().recordSave()
      void syncOfflineCopy(
        target,
        !target.is_bookmarked,
        target.image ? { image: { thumb: '', card: '', hero: target.image.hero } } : null,
      )
      void queryClient.invalidateQueries({ queryKey: ['bookmarks'] })
    },
  })
}
