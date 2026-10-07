/// <reference lib="webworker" />
import { cleanupOutdatedCaches, createHandlerBoundToURL, precacheAndRoute } from 'workbox-precaching'
import { NavigationRoute, registerRoute } from 'workbox-routing'
import { CacheFirst, NetworkFirst, StaleWhileRevalidate } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'
import type { WorkboxPlugin } from 'workbox-core'

declare const self: ServiceWorkerGlobalScope & { __WB_MANIFEST: (string | { url: string; revision: string | null })[] }

// Keep in sync with src/api/offline.ts.
const SAVED_CACHE = 'saved-content-v1'
const CONTENT_CACHE = 'api-content-v1'
const USER_CACHE = 'api-user-v1'
const TAXONOMY_CACHE = 'api-taxonomy-v1'
const IMAGE_CACHE = 'images-v1'

// App shell: hashed build assets and index.html.
cleanupOutdatedCaches()
precacheAndRoute(self.__WB_MANIFEST)

// Client-side routes load the cached shell; API, Sanctum and media requests are never handled as navigations.
registerRoute(
  new NavigationRoute(createHandlerBoundToURL('/index.html'), {
    denylist: [/^\/api\//, /^\/sanctum\//, /^\/storage\//],
  }),
)

/** When network and runtime cache both fail, fall back to the reader's explicitly saved copies. */
const savedCopyFallback: WorkboxPlugin = {
  handlerDidError: async ({ request }) => (await caches.open(SAVED_CACHE)).match(request, { ignoreVary: true }),
}

const isApiGet = (url: URL, request: Request, pattern: RegExp) =>
  request.method === 'GET' && url.pathname.startsWith('/api/v1/') && pattern.test(url.pathname)

// Taxonomy has no personal data: answer instantly, refresh in the background.
registerRoute(
  ({ url, request }) => isApiGet(url, request, /^\/api\/v1\/(industries|sections)$/),
  new StaleWhileRevalidate({ cacheName: TAXONOMY_CACHE, plugins: [new ExpirationPlugin({ maxEntries: 10 })] }),
)

// The signed-in account and saved list: network first, last known copy offline.
registerRoute(
  ({ url, request }) => isApiGet(url, request, /^\/api\/v1\/(me|me\/industries|bookmarks)$/),
  new NetworkFirst({
    cacheName: USER_CACHE,
    networkTimeoutSeconds: 4,
    plugins: [new ExpirationPlugin({ maxEntries: 20 })],
  }),
)

// Feeds, lists and detail pages: network first (fresh bookmark state), cached copy when offline.
// Stale-while-revalidate would briefly show outdated "saved" markers after a toggle.
registerRoute(
  ({ url, request }) =>
    isApiGet(
      url,
      request,
      /^\/api\/v1\/(feed|sections\/[^/]+\/articles|industries\/[^/]+\/feed|articles|events|digests)/,
    ),
  new NetworkFirst({
    cacheName: CONTENT_CACHE,
    networkTimeoutSeconds: 4,
    plugins: [
      new ExpirationPlugin({ maxEntries: 150, maxAgeSeconds: 7 * 24 * 60 * 60, purgeOnQuotaError: true }),
      savedCopyFallback,
    ],
  }),
)

// Article and event images: cache-first with size and age limits; saved stories keep their hero image.
registerRoute(
  ({ request, url }) => request.destination === 'image' && url.pathname.startsWith('/storage/'),
  new CacheFirst({
    cacheName: IMAGE_CACHE,
    plugins: [
      new ExpirationPlugin({ maxEntries: 300, maxAgeSeconds: 30 * 24 * 60 * 60, purgeOnQuotaError: true }),
      savedCopyFallback,
    ],
  }),
)

self.addEventListener('message', (event) => {
  const type = (event.data as { type?: string } | null)?.type

  // The "new version available" prompt asks the waiting worker to take over.
  if (type === 'SKIP_WAITING') void self.skipWaiting()
})
