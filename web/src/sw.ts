/// <reference lib="webworker" />
import { cleanupOutdatedCaches, createHandlerBoundToURL, precacheAndRoute } from 'workbox-precaching'
import { NavigationRoute, registerRoute } from 'workbox-routing'
import { CacheFirst } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'

declare const self: ServiceWorkerGlobalScope & { __WB_MANIFEST: (string | { url: string; revision: string | null })[] }

// App shell: hashed build assets and index.html.
cleanupOutdatedCaches()
precacheAndRoute(self.__WB_MANIFEST)

// Client-side routes load the cached shell; API, Sanctum and media requests are never handled as navigations.
registerRoute(
  new NavigationRoute(createHandlerBoundToURL('/index.html'), {
    denylist: [/^\/api\//, /^\/sanctum\//, /^\/storage\//],
  }),
)

// Article and event images: cache-first with size and age limits.
registerRoute(
  ({ request, url }) => request.destination === 'image' && url.pathname.startsWith('/storage/'),
  new CacheFirst({
    cacheName: 'images',
    plugins: [new ExpirationPlugin({ maxEntries: 300, maxAgeSeconds: 30 * 24 * 60 * 60, purgeOnQuotaError: true })],
  }),
)

// The "new version available" prompt asks the waiting worker to take over.
self.addEventListener('message', (event) => {
  if ((event.data as { type?: string } | null)?.type === 'SKIP_WAITING') {
    void self.skipWaiting()
  }
})
