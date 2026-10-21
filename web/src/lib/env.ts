/**
 * Public site origin (landing, share pages, legal pages). Never hardcoded: the app runs on "app.<domain>", so the
 * site is the same address without "app.". VITE_SITE_URL overrides it (local dev, where the site is on another port).
 */
export function siteUrlFor(location: Pick<Location, 'protocol' | 'host'>, override?: string): string {
  if (override) return override.replace(/\/+$/, '')

  return `${location.protocol}//${location.host.replace(/^app\./, '')}`
}

export const SITE_URL = siteUrlFor(window.location, import.meta.env.VITE_SITE_URL as string | undefined)
