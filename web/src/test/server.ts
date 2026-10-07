import { http, HttpResponse } from 'msw'
import { setupServer } from 'msw/node'
import type { MyIndustries, NotificationPreferences, User } from '@/api/schemas'
import { articleFixture, industriesFixture, preferencesFixture, userFixture } from './fixtures'

/** In-memory backend used by component tests; tests mutate `db` to set up scenarios. */
export const db: {
  user: User | null
  myIndustries: MyIndustries
  preferences: NotificationPreferences
  requests: { method: string; path: string; body: unknown; headers: Headers }[]
} = {
  user: null,
  myIndustries: { industry_ids: [], notify_ids: [] },
  preferences: { ...preferencesFixture },
  requests: [],
}

export function resetDb(): void {
  db.user = null
  db.myIndustries = { industry_ids: [], notify_ids: [] }
  db.preferences = { ...preferencesFixture }
  db.requests = []
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
  db.requests.push({ method: request.method, path: new URL(request.url).pathname, body, headers: request.headers })
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
    HttpResponse.json({ data: [articleFixture()], meta: { next_cursor: null } }),
  ),
]

export const server = setupServer(...handlers)
