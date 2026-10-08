import { z } from 'zod'
import { api } from '@/api/client'
import { dataSchema } from '@/api/schemas'
import { isIos, isStandalone } from '@/hooks/useStandalone'

/**
 * Web Push on this device:
 *  - unsupported: no Push API (old browsers, some in-app browsers);
 *  - install-required: iOS/iPadOS Safari, where push works only from the Home Screen app;
 *  - default / granted / denied: the browser's notification permission.
 */
export type PushAvailability = 'unsupported' | 'install-required' | NotificationPermission

export function pushAvailability(): PushAvailability {
  const hasApi = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window

  if (isIos() && !isStandalone()) return 'install-required'
  if (!hasApi) return 'unsupported'

  return Notification.permission
}

/** VAPID keys are URL-safe base64; PushManager wants raw bytes. */
export function urlBase64ToUint8Array(base64: string): Uint8Array<ArrayBuffer> {
  const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(padded)
  const bytes = new Uint8Array(new ArrayBuffer(raw.length))
  for (let index = 0; index < raw.length; index++) bytes[index] = raw.charCodeAt(index)

  return bytes
}

async function registration(): Promise<ServiceWorkerRegistration> {
  return navigator.serviceWorker.ready
}

export async function currentSubscription(): Promise<PushSubscription | null> {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return null

  return (await registration()).pushManager.getSubscription()
}

async function saveSubscription(subscription: PushSubscription): Promise<void> {
  const json = subscription.toJSON()

  await api.post('/push/subscriptions', {
    endpoint: json.endpoint,
    keys: { p256dh: json.keys?.p256dh, auth: json.keys?.auth },
    content_encoding:
      (PushManager as unknown as { supportedContentEncodings?: string[] }).supportedContentEncodings?.includes(
        'aes128gcm',
      ) === false
        ? 'aesgcm'
        : 'aes128gcm',
  })
}

/**
 * Asks for permission (must run from a user gesture), subscribes this browser and registers it with the API.
 * Returns the resulting permission.
 */
export async function enablePush(): Promise<NotificationPermission> {
  const permission = await Notification.requestPermission()
  if (permission !== 'granted') return permission

  const { data } = await api.get('/push/public-key', {
    schema: dataSchema(z.object({ public_key: z.string().nullable() })),
  })
  if (!data.public_key) throw new Error('Push is not configured on the server.')

  const pushManager = (await registration()).pushManager
  const subscription =
    (await pushManager.getSubscription()) ??
    (await pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(data.public_key),
    }))

  await saveSubscription(subscription)

  return permission
}

/** Unsubscribes this browser and forgets it on the server (the permission itself stays with the browser). */
export async function disablePush(): Promise<void> {
  const subscription = await currentSubscription()
  if (!subscription) return

  await api.delete('/push/subscriptions', { endpoint: subscription.endpoint }).catch(() => undefined)
  await subscription.unsubscribe()
}

/** After sign-in on a browser that already has a subscription, attach it to the new account. */
export async function resyncPush(): Promise<void> {
  if (pushAvailability() !== 'granted') return
  const subscription = await currentSubscription()
  if (subscription) await saveSubscription(subscription)
}
