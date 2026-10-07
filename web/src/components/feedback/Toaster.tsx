import { useEffect } from 'react'
import { useUiStore } from '@/stores/ui'

export const TOAST_MS = 3200

/** One toast at a time, above the bottom navigation on phones and bottom-right on desktop. */
export function Toaster() {
  const toast = useUiStore((state) => state.toast)
  const dismiss = useUiStore((state) => state.dismissToast)

  useEffect(() => {
    if (!toast) return
    const timer = window.setTimeout(dismiss, TOAST_MS)
    return () => window.clearTimeout(timer)
  }, [toast, dismiss])

  return (
    <div
      aria-live="polite"
      className="pointer-events-none fixed inset-x-0 bottom-24 z-[70] mx-auto flex w-[calc(100%-2rem)] max-w-[448px] justify-center lg:right-6 lg:bottom-6 lg:left-auto lg:mx-0 lg:justify-end"
    >
      {toast && (
        <p
          key={toast.id}
          role="status"
          className="pointer-events-auto rounded-control bg-ink px-space-md py-3 text-body-sm text-white shadow-float"
        >
          {toast.message}
        </p>
      )}
    </div>
  )
}
