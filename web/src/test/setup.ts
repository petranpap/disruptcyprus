import '@testing-library/jest-dom/vitest'
import { cleanup, configure } from '@testing-library/react'
import { afterAll, afterEach, beforeAll, vi } from 'vitest'
import i18n from '@/i18n'
import { resetDb, server } from './server'

// Lazy route chunks load on first use; give async queries room.
configure({ asyncUtilTimeout: 5000 })

// jsdom gaps.
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addEventListener: () => undefined,
    removeEventListener: () => undefined,
    addListener: () => undefined,
    removeListener: () => undefined,
    dispatchEvent: () => false,
  }),
})
HTMLDialogElement.prototype.showModal ??= function showModal(this: HTMLDialogElement) {
  this.setAttribute('open', '')
}
HTMLDialogElement.prototype.close ??= function close(this: HTMLDialogElement) {
  this.removeAttribute('open')
}
window.scrollTo = () => undefined

vi.mock('virtual:pwa-register/react', () => ({
  useRegisterSW: () => ({
    needRefresh: [false, () => undefined],
    offlineReady: [false, () => undefined],
    updateServiceWorker: async () => undefined,
  }),
}))

beforeAll(async () => {
  server.listen({ onUnhandledRequest: 'error' })
  await i18n.changeLanguage('en')
})

afterEach(async () => {
  cleanup()
  await i18n.changeLanguage('en')
  server.resetHandlers()
  resetDb()
  window.localStorage.clear()
  document.cookie.split(';').forEach((cookie) => {
    document.cookie = `${cookie.split('=')[0]?.trim()}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`
  })
})

afterAll(() => server.close())
