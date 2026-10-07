import { QueryClient } from '@tanstack/react-query'
import { ApiError } from './errors'

export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 60_000,
        gcTime: 30 * 60_000,
        refetchOnWindowFocus: false,
        // Always attempt the request: offline, the service worker answers from its caches (saved stories, recent feeds).
        networkMode: 'offlineFirst',
        // Never retry client errors (401/403/404/422); retry transient failures twice.
        retry: (failureCount, error) => !(error instanceof ApiError && error.status < 500) && failureCount < 2,
      },
      mutations: { retry: false, networkMode: 'offlineFirst' },
    },
  })
}
