import { http, HttpResponse } from 'msw'
import { z } from 'zod'
import { server } from '@/test/server'
import { api, buildUrl } from './client'
import { ApiError } from './errors'

describe('api client', () => {
  it('sends the UI language, credentials and the XSRF token', async () => {
    document.cookie = 'XSRF-TOKEN=abc%3D%3D; path=/'
    let headers: Headers | undefined
    server.use(
      http.post('*/api/v1/echo', ({ request }) => {
        headers = request.headers
        return HttpResponse.json({ ok: true })
      }),
    )

    await api.post('/echo', { hello: 'world' })

    expect(headers?.get('accept-language')).toBe('en')
    expect(headers?.get('x-xsrf-token')).toBe('abc==')
    expect(headers?.get('content-type')).toBe('application/json')
  })

  it('throws ApiError with field errors from the envelope', async () => {
    server.use(
      http.post('*/api/v1/fail', () =>
        HttpResponse.json(
          { message: 'Invalid', code: 'validation_failed', errors: { email: ['Taken'] } },
          { status: 422 },
        ),
      ),
    )

    const error = await api.post('/fail').catch((caught: unknown) => caught)

    expect(error).toBeInstanceOf(ApiError)
    expect((error as ApiError).code).toBe('validation_failed')
    expect((error as ApiError).fieldErrors()).toEqual({ email: 'Taken' })
  })

  it('refreshes the CSRF cookie and retries once after a 419', async () => {
    let attempts = 0
    server.use(
      http.put('*/api/v1/retry', () => {
        attempts++
        return attempts === 1
          ? HttpResponse.json({ message: 'CSRF token mismatch.', code: 'csrf_mismatch' }, { status: 419 })
          : HttpResponse.json({ ok: true })
      }),
    )

    await expect(api.put('/retry')).resolves.toEqual({ ok: true })
    expect(attempts).toBe(2)
  })

  it('returns undefined for 204 and validates with zod schemas', async () => {
    server.use(
      http.delete('*/api/v1/thing', () => new HttpResponse(null, { status: 204 })),
      http.get('*/api/v1/thing', () => HttpResponse.json({ data: { id: 'not-a-number' } })),
    )

    await expect(api.delete('/thing')).resolves.toBeUndefined()
    await expect(api.get('/thing', { schema: z.object({ data: z.object({ id: z.number() }) }) })).rejects.toThrow()
  })

  it('serializes array filters as industry[]', () => {
    expect(buildUrl('/events', { industry: ['fintech', 'ai'], online: true, city: '' })).toBe(
      'http://localhost:3000/api/v1/events?industry%5B%5D=fintech&industry%5B%5D=ai&online=true',
    )
  })
})
