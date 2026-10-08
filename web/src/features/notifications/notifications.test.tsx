import { act, screen, waitFor, within } from '@testing-library/react'
import type { InboxNotification } from '@/api/schemas'
import { urlBase64ToUint8Array } from '@/lib/push'
import { useEngagementStore, type BeforeInstallPromptEvent } from '@/stores/engagement'
import { userFixture } from '@/test/fixtures'
import { renderApp } from '@/test/render'
import { db } from '@/test/server'

function inboxItem(overrides: Partial<InboxNotification> = {}): InboxNotification {
  return {
    id: crypto.randomUUID(),
    type: 'digest_published',
    title: 'Your Daily News is ready',
    body: 'Top story: Seed round closes',
    url: '/digests/daily-news-2026-10-07',
    read_at: null,
    created_at: new Date().toISOString(),
    ...overrides,
  }
}

describe('inbox', () => {
  it('groups notifications into today and earlier and shows the unread badge', async () => {
    db.user = userFixture
    db.notifications = [
      inboxItem(),
      inboxItem({
        title: 'Reminder: Pitch Night',
        type: 'event_reminder',
        read_at: '2026-10-01T10:00:00Z',
        created_at: '2026-10-01T09:00:00Z',
      }),
    ]
    renderApp('/notifications')

    const today = await screen.findByRole('region', { name: 'Today' })
    expect(within(today).getByText('Your Daily News is ready')).toBeInTheDocument()
    const earlier = screen.getByRole('region', { name: 'Earlier' })
    expect(within(earlier).getByText('Reminder: Pitch Night')).toBeInTheDocument()

    expect(await screen.findByRole('link', { name: /1 unread/i })).toBeInTheDocument()
  })

  it('opens a notification and marks it read', async () => {
    db.user = userFixture
    const item = inboxItem()
    db.notifications = [item]
    const { router, user } = renderApp('/notifications')

    await user.click(await screen.findByRole('button', { name: /Your Daily News is ready/ }))

    await waitFor(() => expect(router.state.location.pathname).toBe('/digests/daily-news-2026-10-07'))
    expect(db.requests.some((request) => request.path === `/api/v1/notifications/${item.id}/read`)).toBe(true)
  })

  it('marks everything as read', async () => {
    db.user = userFixture
    db.notifications = [inboxItem(), inboxItem({ title: 'Featured in Fintech', type: 'featured_article' })]
    const { user } = renderApp('/notifications')

    const button = await screen.findByRole('button', { name: 'Mark all as read' })
    await user.click(button)

    await waitFor(() => expect(button).toBeDisabled())
    expect(db.notifications.every((item) => item.read_at !== null)).toBe(true)
  })

  it('shows an empty state', async () => {
    db.user = userFixture
    renderApp('/notifications')

    expect(await screen.findByText("You're all caught up")).toBeInTheDocument()
  })

  it('invites guests to create an account', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    renderApp('/notifications')

    expect(await screen.findByText('Never miss what matters')).toBeInTheDocument()
  })
})

describe('push card', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    Reflect.deleteProperty(navigator, 'serviceWorker')
  })

  it('explains when the browser has no push support', async () => {
    db.user = userFixture
    renderApp('/settings/notifications')

    expect(await screen.findByText(/doesn't support push notifications/)).toBeInTheDocument()
  })

  it('explains how to unblock notifications', async () => {
    db.user = userFixture
    vi.stubGlobal('Notification', { permission: 'denied', requestPermission: vi.fn() })
    vi.stubGlobal('PushManager', function PushManager() {})
    Object.defineProperty(navigator, 'serviceWorker', {
      value: { ready: new Promise(() => undefined), addEventListener: vi.fn(), removeEventListener: vi.fn() },
      configurable: true,
    })

    renderApp('/settings/notifications')

    expect(await screen.findByText(/blocked for Disrupt Cyprus/)).toBeInTheDocument()
  })
})

describe('install prompt', () => {
  it('offers to install after three articles, once the first minute has passed, and snoozes on dismiss', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    const prompt = vi.fn(async () => undefined)
    useEngagementStore.setState({
      reads: 3,
      sessionStartedAt: Date.now() - 61_000,
      installEvent: {
        prompt,
        userChoice: Promise.resolve({ outcome: 'accepted' }),
      } as unknown as BeforeInstallPromptEvent,
    })
    const { user } = renderApp('/')

    const dialog = await screen.findByRole('dialog', { name: 'Install Disrupt Cyprus' })
    await user.click(within(dialog).getByRole('button', { name: 'Not now' }))

    expect(screen.queryByRole('dialog', { name: 'Install Disrupt Cyprus' })).not.toBeInTheDocument()
    expect(useEngagementStore.getState().installSnoozedUntil).toBeGreaterThan(Date.now())
  })

  it('stays quiet during the first minute of a visit', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    useEngagementStore.setState({
      reads: 5,
      installEvent: { prompt: vi.fn() } as unknown as BeforeInstallPromptEvent,
    })
    renderApp('/')

    await screen.findAllByRole('link', { name: /Disrupt Cyprus/ })
    await act(async () => undefined)
    expect(screen.queryByRole('dialog', { name: 'Install Disrupt Cyprus' })).not.toBeInTheDocument()
  })
})

it('decodes VAPID keys from URL-safe base64', () => {
  expect(Array.from(urlBase64ToUint8Array('AQID_-8'))).toEqual([1, 2, 3, 255, 239])
})
