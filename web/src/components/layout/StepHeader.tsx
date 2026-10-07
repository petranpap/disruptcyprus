import { useTranslation } from 'react-i18next'
import { IconButton } from '@/components/ui/IconButton'
import { LogoMark } from '@/components/ui/Logo'

interface StepHeaderProps {
  current: number
  total: number
  onBack?: () => void
  onSkip?: () => void
}

/** Onboarding header: back, "Step n of 3" with progress track, brand mark, skip. */
export function StepHeader({ current, total, onBack, onSkip }: StepHeaderProps) {
  const { t } = useTranslation()
  const progress = Math.round((current / total) * 100)

  return (
    <header className="sticky top-0 z-40 glass px-space-md py-space-sm pt-safe shadow-sm">
      <div className="mx-auto flex w-full max-w-narrow items-center justify-between lg:px-8">
        <div className="flex items-center gap-space-sm">
          {onBack ? (
            <IconButton icon="arrow_back" label={t('common.back')} size={20} onClick={onBack} className="-ml-2" />
          ) : (
            <span className="w-2" />
          )}
          <div className="flex flex-col">
            <span className="text-label-md font-semibold tracking-wider text-primary">
              {t('onboarding.step', { current, total })}
            </span>
            <div
              className="mt-1 h-1.5 w-24 overflow-hidden rounded-pill bg-surface-container-high"
              role="progressbar"
              aria-valuemin={0}
              aria-valuemax={total}
              aria-valuenow={current}
              aria-label={t('onboarding.step', { current, total })}
            >
              <div
                className="h-full rounded-pill bg-brand-cyan transition-all duration-300"
                style={{ width: `${progress}%` }}
              />
            </div>
          </div>
        </div>
        <LogoMark className="size-6" />
        {onSkip ? (
          <button
            type="button"
            onClick={onSkip}
            className="min-h-tap rounded-control px-2.5 text-label-lg text-on-surface-variant transition-colors hover:text-primary"
          >
            {t('common.skip')}
          </button>
        ) : (
          <span className="w-12" />
        )}
      </div>
    </header>
  )
}
