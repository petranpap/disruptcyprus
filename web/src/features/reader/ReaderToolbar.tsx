import { useEffect, useState, type ReactNode } from 'react'
import { useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { IconButton } from '@/components/ui/IconButton'
import { LogoMark } from '@/components/ui/Logo'

/** Sticky reader bar: back, brand mark, actions, and a cyan reading-progress line. */
export function ReaderToolbar({ actions, showProgress = true }: { actions?: ReactNode; showProgress?: boolean }) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [progress, setProgress] = useState(0)

  useEffect(() => {
    if (!showProgress) return
    const update = () => {
      const scrollable = document.documentElement.scrollHeight - window.innerHeight
      setProgress(scrollable > 0 ? Math.min(100, Math.max(0, (window.scrollY / scrollable) * 100)) : 0)
    }
    update()
    window.addEventListener('scroll', update, { passive: true })
    return () => window.removeEventListener('scroll', update)
  }, [showProgress])

  return (
    <div className="sticky top-0 z-30 glass pt-safe lg:top-16 lg:pt-0">
      <div className="mx-auto flex w-full max-w-reading items-center justify-between px-space-md py-space-xs lg:px-8">
        <IconButton
          icon="arrow_back"
          label={t('common.back')}
          size={24}
          className="-ml-2 text-on-surface"
          onClick={() => (window.history.length > 1 ? navigate(-1) : navigate('/'))}
        />
        <span className="flex items-center gap-1.5 lg:hidden" aria-hidden="true">
          <LogoMark className="size-5" />
          <span className="font-headline text-headline-sm font-bold tracking-tight text-on-surface">
            Disrupt Cyprus
          </span>
        </span>
        <div className="-mr-1 flex items-center">{actions}</div>
      </div>
      {showProgress && (
        <div
          className="h-[2.5px] w-full bg-outline-variant/20"
          role="progressbar"
          aria-label={t('reader.progress')}
          aria-valuemin={0}
          aria-valuemax={100}
          aria-valuenow={Math.round(progress)}
        >
          <div className="h-full bg-brand-cyan transition-[width] duration-150" style={{ width: `${progress}%` }} />
        </div>
      )}
    </div>
  )
}
