import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import { setLanguageProvider } from '@/api/client'
import type { Locale } from '@/api/schemas'
import { STORAGE_KEYS, storage } from '@/lib/storage'
import el from './el.json'
import en from './en.json'

export const LOCALES: Locale[] = ['el', 'en']

/** Stored choice → browser language (Greek unless the browser prefers English) → Greek. */
export function detectLocale(): Locale {
  const stored = storage.get(STORAGE_KEYS.locale)
  if (stored === 'el' || stored === 'en') return stored

  const browser = (navigator.languages?.[0] ?? navigator.language ?? 'el').toLowerCase()

  return browser.startsWith('en') ? 'en' : 'el'
}

export function applyDocumentLanguage(locale: Locale): void {
  document.documentElement.lang = locale
}

void i18n.use(initReactI18next).init({
  resources: { el: { translation: el }, en: { translation: en } },
  lng: detectLocale(),
  fallbackLng: 'el',
  supportedLngs: LOCALES,
  interpolation: { escapeValue: false },
  returnNull: false,
})

applyDocumentLanguage(i18n.language as Locale)
i18n.on('languageChanged', (language) => applyDocumentLanguage(language as Locale))
setLanguageProvider(() => i18n.language)

export function currentLocale(): Locale {
  return i18n.language === 'en' ? 'en' : 'el'
}

export default i18n
