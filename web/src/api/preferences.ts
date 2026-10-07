import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from './client'
import { dataSchema, notificationPreferencesSchema, type NotificationPreferences } from './schemas'

const key = ['me', 'notification-preferences'] as const

export function useNotificationPreferences(enabled = true) {
  return useQuery({
    queryKey: key,
    queryFn: async () =>
      (await api.get('/me/notification-preferences', { schema: dataSchema(notificationPreferencesSchema) })).data,
    enabled,
  })
}

export function useUpdateNotificationPreferences() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input: Partial<NotificationPreferences>) =>
      (await api.put('/me/notification-preferences', input, { schema: dataSchema(notificationPreferencesSchema) }))
        .data,
    onSuccess: (data) => queryClient.setQueryData(key, data),
  })
}
