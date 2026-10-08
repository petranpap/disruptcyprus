import { http, HttpResponse } from 'msw'
import { setupServer } from 'msw/node'
import type { InboxNotification, MyIndustries, NotificationPreferences, User } from '@/api/schemas'
import {
  articleDetailFixture,
  articleFixture,
  calendarFixture,
  digestFixture,
  eventDetailFixture,
  eventFixture,
  industriesFixture,
  preferencesFixture,
  userFixture,
} from './fixtures'

/** In-memory backend used by component tests; tests mutate `db` to set up scenarios. */
export const db: {
  user: User | null
  myIndustries: MyIndustries
  preferences: NotificationPreferences
  requests: { method: string; path: string; body: unknown; headers: Headers; search: string }[]
  bookmarks: Set<string>
  notifications: InboxNotification[]
} = {
  user: null,
  myIndustries: { industry_ids: [], notify_ids: [] },
  preferences: { ...preferencesFixture },
  requests: [],
  bookmarks: new Set(),
  notifications: [],
}

export function resetDb(): void {
  db.user = null
  db.myIndustries = { industry_ids: [], notify_ids: [] }
  db.preferences = { ...preferencesFixture }
  db.requests = []
  db.bookmarks = new Set()
  db.notifications = []
}

const unauthenticated = () =>
  HttpResponse.json({ message: 'Unauthenticated.', code: 'unauthenticated' }, { status: 401 })

async function record(request: Request): Promise<unknown> {
  const body =
    request.method === 'GET'
      ? null
      : await request
          .clone()
          .json()
          .catch(() => null)
  const url = new URL(request.url)
  db.requests.push({ method: request.method, path: url.pathname, body, headers: request.headers, search: url.search })
  return body
}

export const handlers = [
  http.get('*/sanctum/csrf-cookie', () => new HttpResponse(null, { status: 204 })),

  http.get('*/api/v1/me', async ({ request }) => {
    await record(request)
    return db.user ? HttpResponse.json({ data: db.user }) : unauthenticated()
  }),

  http.patch('*/api/v1/me', async ({ request }) => {
    const body = (await record(request)) as Record<string, unknown>
    if (!db.user) return unauthenticated()
    const { onboarding_completed, consent, ...rest } = body
    db.user = {
      ...db.user,
      ...(rest as Partial<User>),
      ...(consent ? { needs_consent: false } : {}),
      ...(onboarding_completed !== undefined ? { onboarded: Boolean(onboarding_completed) } : {}),
    }
    return HttpResponse.json({ data: db.user })
  }),

  http.post('*/api/v1/auth/login', async ({ request }) => {
    const body = (await record(request)) as { email: string; password: string }
    if (body.password !== 'password') {
      return HttpResponse.json(
        {
          message: 'These credentials do not match our records.',
          code: 'validation_failed',
          errors: { email: ['These credentials do not match our records.'] },
        },
        { status: 422 },
      )
    }
    db.user = { ...userFixture, email: body.email }
    return HttpResponse.json({ data: db.user })
  }),

  http.post('*/api/v1/auth/register', async ({ request }) => {
    const body = (await record(request)) as { name: string; email: string }
    if (body.email === 'taken@example.com') {
      return HttpResponse.json(
        {
          message: 'The email has already been taken.',
          code: 'validation_failed',
          errors: { email: ['The email has already been taken.'] },
        },
        { status: 422 },
      )
    }
    db.user = { ...userFixture, name: body.name, email: body.email, onboarded: false, email_verified: false }
    return HttpResponse.json({ data: db.user }, { status: 201 })
  }),

  http.post('*/api/v1/auth/logout', async ({ request }) => {
    await record(request)
    db.user = null
    return new HttpResponse(null, { status: 204 })
  }),

  http.post('*/api/v1/auth/forgot-password', async ({ request }) => {
    await record(request)
    return HttpResponse.json({ message: 'If an account exists for this email, we have sent a password reset link.' })
  }),

  http.get('*/api/v1/industries', () => HttpResponse.json({ data: industriesFixture })),

  http.get('*/api/v1/me/industries', () =>
    db.user ? HttpResponse.json({ data: db.myIndustries }) : unauthenticated(),
  ),
  http.put('*/api/v1/me/industries', async ({ request }) => {
    db.myIndustries = (await record(request)) as MyIndustries
    return HttpResponse.json({ data: db.myIndustries })
  }),

  http.get('*/api/v1/me/notification-preferences', () =>
    db.user ? HttpResponse.json({ data: db.preferences }) : unauthenticated(),
  ),
  http.put('*/api/v1/me/notification-preferences', async ({ request }) => {
    db.preferences = { ...db.preferences, ...((await record(request)) as Partial<NotificationPreferences>) }
    return HttpResponse.json({ data: db.preferences })
  }),

  http.get('*/api/v1/feed/trending', () =>
    HttpResponse.json({
      data: [
        articleFixture(),
        articleFixture({ id: 2, slug: 'second', title: 'Second trending story', is_original: false }),
        articleFixture({ id: 3, slug: 'third', title: 'Third trending story', is_original: false }),
      ].map(withBookmark),
      meta: { next_cursor: null },
    }),
  ),

  http.get('*/api/v1/feed/for-you', async ({ request }) => {
    await record(request)
    if (!db.user) return unauthenticated()
    const fallback = db.myIndustries.industry_ids.length === 0
    return HttpResponse.json({
      data: [articleFixture({ id: 31, slug: 'for-you-lead', title: 'Lead story for you' }), eventFixture()].map(
        withBookmark,
      ),
      meta: { next_cursor: null, fallback: fallback ? 'trending' : null },
    })
  }),

  http.get('*/api/v1/sections/:section/articles', async ({ request, params }) => {
    await record(request)
    return HttpResponse.json({
      data: [articleFixture({ id: 41, slug: 'section-lead', title: `Lead in ${String(params.section)}` })].map(
        withBookmark,
      ),
      meta: { next_cursor: null },
    })
  }),

  http.get('*/api/v1/industries/:slug/feed', ({ params }) =>
    HttpResponse.json({
      data: [articleFixture({ id: 51, title: 'Industry story' })].map(withBookmark),
      meta: { next_cursor: null, industry: { ...industriesFixture[0], slug: String(params.slug) } },
    }),
  ),

  http.get('*/api/v1/articles/:slug/related', () =>
    HttpResponse.json({ data: [articleFixture({ id: 61, slug: 'related-one', title: 'Related story' })] }),
  ),
  http.get('*/api/v1/articles/:slug', ({ params }) =>
    HttpResponse.json({ data: withBookmark(articleDetailFixture({ slug: String(params.slug) })) }),
  ),
  http.post('*/api/v1/articles/:id/view', async ({ request }) => {
    await record(request)
    return new HttpResponse(null, { status: 204 })
  }),

  http.get('*/api/v1/events/calendar', () => HttpResponse.json({ data: calendarFixture })),
  http.get('*/api/v1/events/:slug', ({ params }) =>
    HttpResponse.json({ data: withBookmark(eventDetailFixture({ slug: String(params.slug) })) }),
  ),
  http.get('*/api/v1/events', async ({ request }) => {
    await record(request)
    const range = new URL(request.url).searchParams.get('range')
    return HttpResponse.json({
      data: [eventFixture()].map(withBookmark),
      meta: {
        next_cursor: null,
        digest:
          range === 'week'
            ? { slug: 'weekly-events-2026-W41', title: 'Events this week — 5–11 October', intro: 'Where to be.' }
            : null,
      },
    })
  }),

  http.get('*/api/v1/digests/latest', () => HttpResponse.json({ data: digestFixture() })),
  http.get('*/api/v1/digests/:slug', ({ params }) =>
    HttpResponse.json({ data: digestFixture({ slug: String(params.slug) }) }),
  ),
  http.get('*/api/v1/digests', () =>
    HttpResponse.json({
      data: [digestFixture({ slug: 'daily-news-2026-10-06', title: 'Daily News — 6 October 2026' })],
      meta: { next_cursor: null },
    }),
  ),

  http.get('*/api/v1/search', ({ request }) => {
    const query = new URL(request.url).searchParams.get('q') ?? ''
    return HttpResponse.json({
      data: query.includes('none')
        ? { articles: [], events: [], industries: [] }
        : {
            articles: [articleFixture({ id: 71, title: `Result for ${query}` })],
            events: [],
            industries: [industriesFixture[0]],
          },
    })
  }),

  http.get('*/api/v1/bookmarks', ({ request }) => {
    if (!db.user) return unauthenticated()
    const type = new URL(request.url).searchParams.get('type')
    const items =
      type === 'event'
        ? []
        : [...db.bookmarks]
            .filter((key) => key.startsWith('article:'))
            .map((key) => ({
              ...articleFixture({ id: Number(key.split(':')[1]), title: 'Saved story' }),
              is_bookmarked: true,
              bookmarked_at: '2026-10-07T08:00:00+00:00',
            }))
    return HttpResponse.json({ data: items, meta: { next_cursor: null } })
  }),
  http.post('*/api/v1/bookmarks', async ({ request }) => {
    const body = (await record(request)) as { type: string; id: number }
    db.bookmarks.add(`${body.type}:${body.id}`)
    return HttpResponse.json({ data: { ...body, is_bookmarked: true } }, { status: 201 })
  }),
  http.delete('*/api/v1/bookmarks', async ({ request }) => {
    const body = (await record(request)) as { type: string; id: number }
    db.bookmarks.delete(`${body.type}:${body.id}`)
    return new HttpResponse(null, { status: 204 })
  }),

  http.get('*/api/v1/notifications/unread-count', () =>
    db.user
      ? HttpResponse.json({ data: { count: db.notifications.filter((item) => !item.read_at).length } })
      : unauthenticated(),
  ),
  http.get('*/api/v1/notifications', () =>
    db.user
      ? HttpResponse.json({
          data: db.notifications,
          meta: { next_cursor: null, unread_count: db.notifications.filter((item) => !item.read_at).length },
        })
      : unauthenticated(),
  ),
  http.post('*/api/v1/notifications/read-all', async ({ request }) => {
    await record(request)
    db.notifications = db.notifications.map((item) => ({ ...item, read_at: item.read_at ?? new Date().toISOString() }))
    return new HttpResponse(null, { status: 204 })
  }),
  http.post('*/api/v1/notifications/:id/read', async ({ request, params }) => {
    await record(request)
    db.notifications = db.notifications.map((item) =>
      item.id === params.id ? { ...item, read_at: new Date().toISOString() } : item,
    )
    return new HttpResponse(null, { status: 204 })
  }),
  http.get('*/api/v1/push/public-key', () => HttpResponse.json({ data: { public_key: 'BTestKey' } })),

  http.put('*/api/v1/me/password', async ({ request }) => {
    const body = (await record(request)) as { current_password?: string }
    if (body.current_password !== 'password') {
      return HttpResponse.json(
        {
          message: 'The password is incorrect.',
          code: 'validation_failed',
          errors: { current_password: ['The password is incorrect.'] },
        },
        { status: 422 },
      )
    }
    return HttpResponse.json({ message: 'Your password has been updated.' })
  }),
  http.post('*/api/v1/me/export', async ({ request }) => {
    await record(request)
    return HttpResponse.json({ message: 'Queued' }, { status: 202 })
  }),
  http.delete('*/api/v1/me', async ({ request }) => {
    const body = (await record(request)) as { password?: string }
    if (body.password !== 'password') {
      return HttpResponse.json(
        {
          message: 'The password is incorrect.',
          code: 'validation_failed',
          errors: { password: ['The password is incorrect.'] },
        },
        { status: 422 },
      )
    }
    db.user = null
    return new HttpResponse(null, { status: 204 })
  }),
]

function withBookmark<T extends { type: string; id: number }>(card: T): T {
  return { ...card, is_bookmarked: db.bookmarks.has(`${card.type}:${card.id}`) }
}

export const server = setupServer(...handlers)
