import { create } from 'zustand'
import { STORAGE_KEYS, storage } from '@/lib/storage'

/** Chrome/Edge/Android install prompt, deferred until the reader has shown interest. */
export interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

const DAY_MS = 24 * 60 * 60 * 1000
export const INSTALL_SNOOZE_MS = 14 * DAY_MS
export const READS_BEFORE_INSTALL = 3
/** No prompt of any kind during the first minute of a visit. */
export const QUIET_START_MS = 60_000

interface Persisted {
  reads: number
  saved: boolean
  installSnoozedUntil: number
  pushPromptDone: boolean
}

interface EngagementState extends Persisted {
  sessionStartedAt: number
  /** One prompt per visit at most. */
  promptShownThisSession: boolean
  installEvent: BeforeInstallPromptEvent | null
  recordRead: () => void
  recordSave: () => void
  snoozeInstall: () => void
  finishPushPrompt: () => void
  markPromptShown: () => void
  setInstallEvent: (event: BeforeInstallPromptEvent | null) => void
}

const DEFAULTS: Persisted = { reads: 0, saved: false, installSnoozedUntil: 0, pushPromptDone: false }

function load(): Persisted {
  try {
    return { ...DEFAULTS, ...(JSON.parse(storage.get(STORAGE_KEYS.engagement) ?? '{}') as Partial<Persisted>) }
  } catch {
    return DEFAULTS
  }
}

function persist({ reads, saved, installSnoozedUntil, pushPromptDone }: Persisted): void {
  storage.set(STORAGE_KEYS.engagement, JSON.stringify({ reads, saved, installSnoozedUntil, pushPromptDone }))
}

/** Signals that decide when to suggest installing the app or enabling push (never on first contact). */
export const useEngagementStore = create<EngagementState>((set, get) => ({
  ...load(),
  sessionStartedAt: Date.now(),
  promptShownThisSession: false,
  installEvent: null,
  recordRead: () => {
    set({ reads: get().reads + 1 })
    persist(get())
  },
  recordSave: () => {
    if (get().saved) return
    set({ saved: true })
    persist(get())
  },
  snoozeInstall: () => {
    set({ installSnoozedUntil: Date.now() + INSTALL_SNOOZE_MS })
    persist(get())
  },
  finishPushPrompt: () => {
    set({ pushPromptDone: true })
    persist(get())
  },
  markPromptShown: () => set({ promptShownThisSession: true }),
  setInstallEvent: (installEvent) => set({ installEvent }),
}))

/** Called once at startup: keep the browser's install prompt for later instead of showing the mini-infobar. */
export function captureInstallPrompt(): void {
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    useEngagementStore.getState().setInstallEvent(event as BeforeInstallPromptEvent)
  })
  window.addEventListener('appinstalled', () => useEngagementStore.getState().setInstallEvent(null))
}
