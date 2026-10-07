/** Public site (landing, share pages, legal pages) — a different origin from the PWA in production. */
export const SITE_URL = (import.meta.env.VITE_SITE_URL as string | undefined) ?? 'http://localhost:8080'
