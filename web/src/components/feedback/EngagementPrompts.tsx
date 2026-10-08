import { useEffect, useState } from 'react'
import { Trans, useTranslation } from 'react-i18next'
import { useMe } from '@/api/auth'
import { Button } from '@/components/ui/Button'
import { Icon, type IconName } from '@/components/ui/Icon'
import { IconButton } from '@/components/ui/IconButton'
import { Sheet } from '@/components/ui/Sheet'
import { isIos, isStandalone } from '@/hooks/useStandalone'
import { enablePush, pushAvailability } from '@/lib/push'
import { QUIET_START_MS, READS_BEFORE_INSTALL, useEngagementStore } from '@/stores/engagement'
import { useUiStore } from '@/stores/ui'

type Prompt = 'push' | 'install' | null

/** Re-renders once the quiet first minute of the visit is over. */
function useQuietPeriodOver(startedAt: number): boolean {
  const [over, setOver] = useState(() => Date.now() - startedAt >= QUIET_START_MS)

  useEffect(() => {
    if (over) return
    const timer = window.setTimeout(() => setOver(true), QUIET_START_MS - (Date.now() - startedAt))

    return () => window.clearTimeout(timer)
  }, [over, startedAt])

  return over
}

/**
 * At most one gentle prompt per visit, never in the first minute:
 *  - after the first save, signed-in readers are offered push on this device;
 *  - after 3 articles or the first save, the app install banner (Chrome's prompt, or instructions on iOS),
 *    snoozed for 14 days when dismissed and never shown in the installed app.
 */
export function EngagementPrompts() {
  const { t } = useTranslation()
  const { data: user } = useMe()
  const engagement = useEngagementStore()
  const showToast = useUiStore((state) => state.showToast)
  const quietOver = useQuietPeriodOver(engagement.sessionStartedAt)
  const [iosHelpOpen, setIosHelpOpen] = useState(false)
  const [busy, setBusy] = useState(false)
  const [mountedAt] = useState(() => Date.now())

  const interested = engagement.saved || engagement.reads >= READS_BEFORE_INSTALL
  const canInstall = !isStandalone() && (engagement.installEvent !== null || isIos())
  const wantsPush = Boolean(user) && engagement.saved && !engagement.pushPromptDone && pushAvailability() === 'default'
  const installDue = interested && canInstall && mountedAt >= engagement.installSnoozedUntil

  // Derived, not stored: once the reader acts on a prompt, the session flag hides prompts until the next visit.
  const prompt: Prompt =
    engagement.promptShownThisSession || !quietOver ? null : wantsPush ? 'push' : installDue ? 'install' : null
  const close = () => engagement.markPromptShown()

  if (!prompt && !iosHelpOpen) return null

  const dismiss = () => {
    if (prompt === 'push') engagement.finishPushPrompt()
    if (prompt === 'install') engagement.snoozeInstall()
    close()
  }

  const accept = async () => {
    if (prompt === 'push') {
      setBusy(true)
      try {
        const permission = await enablePush()
        showToast(permission === 'granted' ? t('push.enabledToast') : t('push.deniedToast'))
      } catch {
        showToast(t('push.errorToast'))
      } finally {
        setBusy(false)
        engagement.finishPushPrompt()
        close()
      }
      return
    }

    if (engagement.installEvent) {
      await engagement.installEvent.prompt()
      const { outcome } = await engagement.installEvent.userChoice
      engagement.setInstallEvent(null)
      if (outcome === 'dismissed') engagement.snoozeInstall()
      close()
    } else {
      setIosHelpOpen(true)
      engagement.snoozeInstall()
      close()
    }
  }

  const copy: Record<Exclude<Prompt, null>, { icon: IconName; title: string; body: string; action: string }> = {
    push: {
      icon: 'notifications_active',
      title: t('push.promptTitle'),
      body: t('push.promptBody'),
      action: t('push.turnOn'),
    },
    install: {
      icon: 'download',
      title: t('install.title'),
      body: t('install.body'),
      action: isIos() && !engagement.installEvent ? t('install.howTo') : t('install.action'),
    },
  }

  return (
    <>
      {prompt && (
        <div
          role="dialog"
          aria-labelledby="engagement-title"
          className="fixed inset-x-0 bottom-24 z-[55] mx-auto w-[calc(100%-2rem)] max-w-[448px] lg:right-6 lg:bottom-6 lg:left-auto lg:mx-0"
        >
          <div className="rounded-card border border-card-stroke bg-surface-container-lowest p-space-md shadow-float">
            <div className="flex items-start gap-3">
              <span className="flex size-10 shrink-0 items-center justify-center rounded-pill bg-primary-fixed text-primary">
                <Icon name={copy[prompt].icon} size={22} />
              </span>
              <div className="min-w-0 flex-1">
                <p id="engagement-title" className="text-label-lg text-on-surface">
                  {copy[prompt].title}
                </p>
                <p className="mt-0.5 text-body-sm text-on-surface-variant">{copy[prompt].body}</p>
              </div>
              <IconButton icon="close" label={t('common.close')} onClick={dismiss} className="-mt-2 -mr-2" />
            </div>
            <div className="mt-space-sm flex justify-end gap-2">
              <Button variant="ghost" block={false} className="min-h-10 py-2" onClick={dismiss}>
                {t('common.notNow')}
              </Button>
              <Button block={false} className="min-h-10 py-2" loading={busy} onClick={() => void accept()}>
                {copy[prompt].action}
              </Button>
            </div>
          </div>
        </div>
      )}
      <Sheet open={iosHelpOpen} onClose={() => setIosHelpOpen(false)} title={t('install.title')}>
        <p className="pb-space-lg text-body-md text-on-surface-variant">
          <Trans
            i18nKey="push.installIos"
            components={{
              share: <Icon name="ios_share" size={18} className="-mt-0.5 inline" label={t('common.share')} />,
            }}
          />
        </p>
      </Sheet>
    </>
  )
}
