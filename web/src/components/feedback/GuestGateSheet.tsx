import { useLocation, useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { Sheet } from '@/components/ui/Sheet'
import { useUiStore } from '@/stores/ui'

/** Shown when a guest tries to save, follow or personalize. */
export function GuestGateSheet() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const reason = useUiStore((state) => state.guestGate)
  const close = useUiStore((state) => state.closeGuestGate)

  const go = (path: string) => {
    close()
    // Come back to the story the guest was trying to save.
    navigate(path, { state: { from: location.pathname + location.search } })
  }

  return (
    <Sheet open={reason !== null} onClose={close} title={t(`guest.${reason ?? 'save'}Title`)}>
      <p className="mb-space-lg font-body text-body-md text-on-surface-variant">{t(`guest.${reason ?? 'save'}Body`)}</p>
      <div className="flex flex-col gap-3">
        <Button onClick={() => go('/sign-up')}>{t('welcome.createAccount')}</Button>
        <Button variant="outline" onClick={() => go('/sign-in')}>
          {t('welcome.signIn')}
        </Button>
      </div>
    </Sheet>
  )
}
