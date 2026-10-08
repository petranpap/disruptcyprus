import { Trans, useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { Icon } from '@/components/ui/Icon'
import { Switch } from '@/components/ui/Switch'
import { usePush } from '@/hooks/usePush'
import { useUiStore } from '@/stores/ui'
import { cn } from '@/lib/cn'

interface PushCardProps {
  /**
   * banner: inbox nudge, only while push can still be turned on;
   * settings: matches the other settings cards; card: onboarding list style.
   */
  variant?: 'card' | 'settings' | 'banner'
  className?: string
}

/**
 * Web Push for this device. Covers every browser state: unsupported, iOS before installing,
 * not asked yet, on, off, and blocked in browser settings.
 */
export function PushCard({ variant = 'card', className }: PushCardProps) {
  const { t } = useTranslation()
  const push = usePush()
  const showToast = useUiStore((state) => state.showToast)

  const enable = async () => {
    try {
      const granted = await push.enable()
      showToast(granted ? t('push.enabledToast') : t('push.deniedToast'))
    } catch {
      showToast(t('push.errorToast'))
    }
  }

  const disable = async () => {
    await push.disable()
    showToast(t('push.disabledToast'))
  }

  if (variant === 'banner') {
    const canEnable = push.availability === 'default' || (push.availability === 'granted' && !push.subscribed)
    if (push.loading || !canEnable) return null

    return (
      <div
        className={cn(
          'flex flex-col gap-3 rounded-card bg-primary-fixed p-space-md text-on-primary-fixed-variant sm:flex-row sm:items-center',
          className,
        )}
      >
        <div className="flex flex-1 items-start gap-3 sm:items-center">
          <Icon name="notifications_active" size={24} className="shrink-0" />
          <p className="text-body-sm">{t('push.inboxBanner')}</p>
        </div>
        <Button block={false} className="min-h-10 py-2" loading={push.busy} onClick={() => void enable()}>
          {t('push.turnOn')}
        </Button>
      </div>
    )
  }

  return (
    <section
      className={cn(
        'rounded-card border border-card-stroke bg-surface-container-lowest px-space-md',
        variant === 'settings' && 'pt-space-md',
        className,
      )}
      aria-labelledby="push-title"
    >
      <h2
        id="push-title"
        className={
          variant === 'settings'
            ? 'font-headline text-headline-sm text-on-surface'
            : 'pt-space-md text-label-md tracking-wider text-on-surface-variant'
        }
      >
        {t('push.title')}
      </h2>
      <PushControls push={push} enable={enable} disable={disable} />
    </section>
  )
}

interface PushControlsProps {
  push: ReturnType<typeof usePush>
  enable: () => Promise<void>
  disable: () => Promise<void>
}

/** The state-specific body: explanation or the on/off switch. */
function PushControls({ push, enable, disable }: PushControlsProps) {
  const { t } = useTranslation()

  return (
    <>
      {push.availability === 'install-required' && (
        <p className="flex gap-3 py-space-md text-body-sm text-on-surface-variant">
          <Icon name="ios_share" size={20} className="mt-0.5 shrink-0 text-primary" />
          <span>
            <Trans
              i18nKey="push.installIos"
              components={{
                share: <Icon name="ios_share" size={16} className="-mt-0.5 inline" label={t('common.share')} />,
              }}
            />
          </span>
        </p>
      )}

      {push.availability === 'unsupported' && (
        <p className="py-space-md text-body-sm text-on-surface-variant">{t('push.unsupported')}</p>
      )}

      {push.availability === 'denied' && (
        <p className="flex gap-3 py-space-md text-body-sm text-on-surface-variant">
          <Icon name="notifications_off" size={20} className="mt-0.5 shrink-0 text-error" />
          <span>{t('push.denied')}</span>
        </p>
      )}

      {(push.availability === 'default' || push.availability === 'granted') && (
        <Switch
          checked={push.subscribed}
          disabled={push.loading || push.busy}
          onChange={(checked) => void (checked ? enable() : disable())}
          label={t('push.deviceLabel')}
          hint={push.subscribed ? t('push.onHint') : t('push.offHint')}
        />
      )}
    </>
  )
}
