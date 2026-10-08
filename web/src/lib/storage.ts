/**
 * localStorage that never throws (private mode, blocked storage, SSR-like test environments).
 * Only for per-device conveniences: anything that must persist lives on the server.
 */
export const storage = {
  get(key: string): string | null {
    try {
      return window.localStorage.getItem(key)
    } catch {
      return null
    }
  },
  set(key: string, value: string): void {
    try {
      window.localStorage.setItem(key, value)
    } catch {
      // Storage unavailable: the preference simply won't persist.
    }
  },
  remove(key: string): void {
    try {
      window.localStorage.removeItem(key)
    } catch {
      // Ignore.
    }
  },
}

export const STORAGE_KEYS = {
  locale: 'dc.locale',
  theme: 'dc.theme',
  welcomed: 'dc.welcomed',
  readerFontSize: 'dc.reader-font-size',
  engagement: 'dc.engagement',
} as const
