/** Cache names shared with src/sw.ts. */
export const SAVED_CACHE = 'saved-content-v1'
export const PERSONAL_CACHES = [SAVED_CACHE, 'api-content-v1', 'api-user-v1']

/** Sign-out and account deletion: remove every cache that can hold this reader's data. */
export async function clearPersonalCaches(): Promise<void> {
  if (!('caches' in window)) return

  await Promise.all(PERSONAL_CACHES.map((name) => caches.delete(name).catch(() => false)))
}
