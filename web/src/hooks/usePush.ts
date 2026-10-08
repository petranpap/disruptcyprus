import { useCallback, useEffect, useState } from 'react'
import { currentSubscription, disablePush, enablePush, pushAvailability, type PushAvailability } from '@/lib/push'

export interface PushState {
  availability: PushAvailability
  /** This browser is subscribed (permission granted and a subscription exists). */
  subscribed: boolean
  loading: boolean
  busy: boolean
  enable: () => Promise<boolean>
  disable: () => Promise<void>
}

/** Push permission + subscription state of this device, with enable/disable actions. */
export function usePush(): PushState {
  const [availability, setAvailability] = useState<PushAvailability>(pushAvailability)
  const [subscribed, setSubscribed] = useState(false)
  const [loading, setLoading] = useState(availability === 'granted')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (availability !== 'granted') return
    let cancelled = false

    void currentSubscription()
      .then((subscription) => {
        if (!cancelled) setSubscribed(subscription !== null)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [availability])

  const enable = useCallback(async () => {
    setBusy(true)
    try {
      const permission = await enablePush()
      setAvailability(permission)
      setSubscribed(permission === 'granted')

      return permission === 'granted'
    } finally {
      setBusy(false)
    }
  }, [])

  const disable = useCallback(async () => {
    setBusy(true)
    try {
      await disablePush()
      setSubscribed(false)
    } finally {
      setBusy(false)
    }
  }, [])

  return { availability, subscribed, loading, busy, enable, disable }
}
