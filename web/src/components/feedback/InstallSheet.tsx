import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { Icon } from '@/components/ui/Icon'
import { Sheet } from '@/components/ui/Sheet'
import { useInstall } from '@/hooks/useInstall'
import { OTHER_DEVICE_PLATFORMS, type InstallPlatform } from '@/lib/platform'
import { useUiStore } from '@/stores/ui'

function Steps({ platform }: { platform: InstallPlatform }) {
  const { t } = useTranslation()
  const steps = t(`install.steps.${platform}`, { returnObjects: true }) as string[]

  return (
    <ol className="space-y-3">
      {steps.map((step, index) => (
        <li key={step} className="flex gap-3 text-body-md text-on-surface">
          <span className="flex size-7 shrink-0 items-center justify-center rounded-pill bg-primary-fixed text-label-md font-bold text-primary">
            {index + 1}
          </span>
          <span className="pt-0.5">{step}</span>
        </li>
      ))}
    </ol>
  )
}

/** How to install the app on this device, detected from the browser (with the other phone type one tap away). */
export function InstallSheet() {
  const { t } = useTranslation()
  const open = useUiStore((state) => state.installSheet)
  const close = useUiStore((state) => state.closeInstallSheet)
  const { installed, canPrompt, platform, install } = useInstall()

  return (
    <Sheet open={open} onClose={close} title={t('install.title')}>
      <div className="space-y-space-lg pb-space-lg">
        {installed ? (
          <p className="flex gap-3 text-body-md text-on-surface-variant">
            <Icon name="check" size={20} className="mt-0.5 shrink-0 text-success" />
            {t('install.installed')}
          </p>
        ) : (
          <>
            <p className="text-body-md text-on-surface-variant">{t('install.intro')}</p>
            {canPrompt && (
              <Button
                icon={<Icon name="download" size={18} />}
                onClick={() => {
                  close()
                  void install()
                }}
              >
                {t('install.oneTap')}
              </Button>
            )}
            <section aria-labelledby="install-here">
              <h3 id="install-here" className="mb-space-sm text-label-md tracking-wider text-on-surface-variant">
                {t(`install.platforms.${platform}`)}
              </h3>
              <Steps platform={platform} />
            </section>
          </>
        )}
        <details className="rounded-card border border-card-stroke px-space-md py-space-sm">
          <summary className="min-h-tap cursor-pointer py-2 text-label-lg text-on-surface">
            {t('install.otherDevice')}
          </summary>
          <div className="space-y-space-md pt-space-sm pb-space-sm">
            {OTHER_DEVICE_PLATFORMS.filter((other) => other !== platform).map((other) => (
              <section key={other} aria-label={t(`install.platforms.${other}`)}>
                <h3 className="mb-space-sm text-label-md tracking-wider text-on-surface-variant">
                  {t(`install.platforms.${other}`)}
                </h3>
                <Steps platform={other} />
              </section>
            ))}
          </div>
        </details>
      </div>
    </Sheet>
  )
}
