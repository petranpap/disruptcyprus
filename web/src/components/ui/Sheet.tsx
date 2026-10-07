import { useEffect, useRef, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { IconButton } from './IconButton'

interface SheetProps {
  open: boolean
  onClose: () => void
  title: string
  children: ReactNode
}

/** Bottom sheet on a native <dialog>: focus trapping, Escape and inert background come for free. */
export function Sheet({ open, onClose, title, children }: SheetProps) {
  const { t } = useTranslation()
  const dialog = useRef<HTMLDialogElement>(null)

  useEffect(() => {
    const element = dialog.current
    if (!element) return
    if (open && !element.open) element.showModal?.()
    if (!open && element.open) element.close?.()
  }, [open])

  return (
    <dialog
      ref={dialog}
      aria-labelledby="sheet-title"
      onCancel={(event) => {
        event.preventDefault()
        onClose()
      }}
      onClick={(event) => {
        if (event.target === dialog.current) onClose()
      }}
      className="fixed inset-x-0 top-auto bottom-0 m-0 mx-auto w-full max-w-app-column rounded-t-[24px] bg-surface-container-lowest p-0 text-on-surface shadow-float backdrop:bg-ink/50 backdrop:backdrop-blur-sm open:animate-[sheet-in_200ms_ease-out]"
    >
      <div className="px-space-md pt-space-sm pb-safe">
        <div className="mx-auto mb-2 h-1 w-10 rounded-pill bg-outline-variant" aria-hidden="true" />
        <div className="flex items-center justify-between gap-2">
          <h2 id="sheet-title" className="font-headline text-headline-md text-on-surface">
            {title}
          </h2>
          <IconButton icon="close" label={t('common.close')} onClick={onClose} />
        </div>
        <div className="pt-space-sm pb-space-md">{children}</div>
      </div>
    </dialog>
  )
}
