import { useMutation, useQuery, useQueryClient, type InfiniteData } from '@tanstack/react-query'
import { z } from 'zod'
import { api } from './client'
import { useCursorList } from './content'
import { cursorPageSchema, dataSchema, inboxNotificationSchema, type InboxNotification } from './schemas'

const listKey = ['notifications'] as const
const countKey = ['notifications', 'unread-count'] as const

/** How often the bell badge refreshes while the app is open (pushes also trigger a refresh). */
const UNREAD_POLL_MS = 60_000

type InboxPage = { data: InboxNotification[]; meta: { next_cursor: string | null } }

export function useNotifications(enabled = true) {
  return useCursorList(
    listKey,
    (cursor) => api.get('/notifications', { query: { cursor }, schema: cursorPageSchema(inboxNotificationSchema) }),
    enabled,
  )
}

export function useUnreadCount(enabled: boolean) {
  return useQuery({
    queryKey: countKey,
    queryFn: async () =>
      (await api.get('/notifications/unread-count', { schema: dataSchema(z.object({ count: z.number() })) })).data
        .count,
    enabled,
    refetchInterval: enabled ? UNREAD_POLL_MS : false,
    refetchIntervalInBackground: false,
  })
}

/** Optimistically marks inbox items read in the cached pages. */
function markCached(queryClient: ReturnType<typeof useQueryClient>, isTarget: (item: InboxNotification) => boolean) {
  const now = new Date().toISOString()
  let changed = 0

  queryClient.setQueryData<InfiniteData<InboxPage>>(listKey, (data) =>
    data
      ? {
          ...data,
          pages: data.pages.map((page) => ({
            ...page,
            data: page.data.map((item) => {
              if (item.read_at || !isTarget(item)) return item
              changed++
              return { ...item, read_at: now }
            }),
          })),
        }
      : data,
  )

  return changed
}

export function useMarkNotificationRead() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: string) => api.post(`/notifications/${id}/read`),
    onMutate: (id) => {
      const changed = markCached(queryClient, (item) => item.id === id)
      queryClient.setQueryData<number>(countKey, (count) => Math.max(0, (count ?? 0) - changed))
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: countKey }),
  })
}

export function useMarkAllNotificationsRead() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => api.post('/notifications/read-all'),
    onMutate: () => {
      markCached(queryClient, () => true)
      queryClient.setQueryData(countKey, 0)
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: listKey }),
  })
}

/** A push arrived (service worker message): refresh the badge and the inbox. */
export function refreshNotifications(queryClient: ReturnType<typeof useQueryClient>) {
  void queryClient.invalidateQueries({ queryKey: listKey })
}
