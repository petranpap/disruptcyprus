import { create } from 'zustand'

export type GuestGateReason = 'save' | 'personalize' | 'follow'

interface UiState {
  guestGate: GuestGateReason | null
  openGuestGate: (reason: GuestGateReason) => void
  closeGuestGate: () => void
  toast: { id: number; message: string } | null
  showToast: (message: string) => void
  dismissToast: () => void
}

let toastId = 0

/** Small cross-screen UI state: the guest sign-up prompt and the single toast. */
export const useUiStore = create<UiState>((set) => ({
  guestGate: null,
  openGuestGate: (reason) => set({ guestGate: reason }),
  closeGuestGate: () => set({ guestGate: null }),
  toast: null,
  showToast: (message) => set({ toast: { id: ++toastId, message } }),
  dismissToast: () => set({ toast: null }),
}))
