import { create } from 'zustand'
import { STORAGE_KEYS, storage } from '@/lib/storage'

export type ThemePreference = 'system' | 'light' | 'dark'
export type ResolvedTheme = 'light' | 'dark'

const media = () => window.matchMedia('(prefers-color-scheme: dark)')

function readPreference(): ThemePreference {
  // ?theme=dark|light (QA/screenshots) wins for this page load without being saved.
  const forced = new URLSearchParams(window.location.search).get('theme')
  if (forced === 'light' || forced === 'dark') return forced

  const stored = storage.get(STORAGE_KEYS.theme)

  return stored === 'light' || stored === 'dark' ? stored : 'system'
}

export function resolveTheme(preference: ThemePreference): ResolvedTheme {
  if (preference !== 'system') return preference

  return media().matches ? 'dark' : 'light'
}

const THEME_COLORS: Record<ResolvedTheme, string> = { light: '#FAFAF8', dark: '#121316' }

/** Applies the theme to <html data-theme> and the browser UI colour (status bar). */
export function applyTheme(theme: ResolvedTheme): void {
  document.documentElement.dataset.theme = theme
  document.querySelector('meta[name="theme-color"]:not([media])')?.setAttribute('content', THEME_COLORS[theme])
}

interface ThemeState {
  preference: ThemePreference
  resolved: ResolvedTheme
  setPreference: (preference: ThemePreference) => void
}

export const useThemeStore = create<ThemeState>((set) => ({
  preference: readPreference(),
  resolved: resolveTheme(readPreference()),
  setPreference: (preference) => {
    if (preference === 'system') storage.remove(STORAGE_KEYS.theme)
    else storage.set(STORAGE_KEYS.theme, preference)

    const resolved = resolveTheme(preference)
    applyTheme(resolved)
    set({ preference, resolved })
  },
}))

/** Follow OS changes while the preference is "system". Returns an unsubscribe function. */
export function watchSystemTheme(): () => void {
  const query = media()
  const listener = () => {
    const { preference } = useThemeStore.getState()
    if (preference === 'system') {
      const resolved = resolveTheme('system')
      applyTheme(resolved)
      useThemeStore.setState({ resolved })
    }
  }

  query.addEventListener('change', listener)

  return () => query.removeEventListener('change', listener)
}
