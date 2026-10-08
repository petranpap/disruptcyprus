import type { z } from 'zod'
import { ApiError, NetworkError } from './errors'

const API_PREFIX = '/api/v1'
const XSRF_COOKIE = 'XSRF-TOKEN'

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

interface RequestOptions<T> {
  body?: unknown
  query?: Record<string, string | number | boolean | string[] | null | undefined>
  schema?: z.ZodType<T>
  signal?: AbortSignal
}

let languageProvider: () => string = () => 'el'

/** The i18n layer registers how to read the current UI language (sent as Accept-Language). */
export function setLanguageProvider(provider: () => string): void {
  languageProvider = provider
}

function readCookie(name: string): string | null {
  const match = document.cookie.split('; ').find((row) => row.startsWith(`${name}=`))

  return match ? decodeURIComponent(match.slice(name.length + 1)) : null
}

let csrfRequest: Promise<void> | null = null

/** Sanctum SPA auth: obtain the XSRF-TOKEN cookie once before the first state-changing request. */
export function ensureCsrfCookie(force = false): Promise<void> {
  if (!force && readCookie(XSRF_COOKIE)) {
    return Promise.resolve()
  }

  csrfRequest ??= fetch(new URL('/sanctum/csrf-cookie', window.location.origin).href, { credentials: 'include' })
    .then(() => undefined)
    .finally(() => {
      csrfRequest = null
    })

  return csrfRequest
}

export function buildUrl(path: string, query?: RequestOptions<unknown>['query']): string {
  const url = new URL(`${API_PREFIX}${path}`, window.location.origin)

  for (const [key, value] of Object.entries(query ?? {})) {
    if (value === null || value === undefined || value === '') continue
    if (Array.isArray(value)) {
      value.forEach((item) => url.searchParams.append(`${key}[]`, item))
    } else {
      url.searchParams.set(key, String(value))
    }
  }

  return url.href
}

async function send<T>(method: Method, path: string, options: RequestOptions<T>, retried: boolean): Promise<T> {
  if (method !== 'GET') {
    await ensureCsrfCookie()
  }

  const isForm = options.body instanceof FormData
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Accept-Language': languageProvider(),
    'X-Requested-With': 'XMLHttpRequest',
  }
  const xsrf = readCookie(XSRF_COOKIE)
  if (xsrf) headers['X-XSRF-TOKEN'] = xsrf
  if (options.body !== undefined && !isForm) headers['Content-Type'] = 'application/json'

  let response: Response
  try {
    response = await fetch(buildUrl(path, options.query), {
      method,
      headers,
      credentials: 'include',
      signal: options.signal,
      body: options.body === undefined ? undefined : isForm ? (options.body as FormData) : JSON.stringify(options.body),
    })
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') throw error
    throw new NetworkError()
  }

  // Expired CSRF token (e.g. after a long idle tab): refresh once and retry.
  if (response.status === 419 && !retried) {
    await ensureCsrfCookie(true)
    return send(method, path, options, true)
  }

  if (response.status === 204) {
    return undefined as T
  }

  const payload: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    const envelope = (payload ?? {}) as { message?: string; code?: string; errors?: Record<string, string[]> }
    throw new ApiError(
      response.status,
      envelope.code ?? 'http_error',
      envelope.message ?? response.statusText,
      envelope.errors,
    )
  }

  return options.schema ? options.schema.parse(payload) : (payload as T)
}

export const api = {
  get: <T>(path: string, options: RequestOptions<T> = {}) => send<T>('GET', path, options, false),
  post: <T>(path: string, body?: unknown, options: RequestOptions<T> = {}) =>
    send<T>('POST', path, { ...options, body }, false),
  put: <T>(path: string, body?: unknown, options: RequestOptions<T> = {}) =>
    send<T>('PUT', path, { ...options, body }, false),
  patch: <T>(path: string, body?: unknown, options: RequestOptions<T> = {}) =>
    send<T>('PATCH', path, { ...options, body }, false),
  delete: <T>(path: string, body?: unknown, options: RequestOptions<T> = {}) =>
    send<T>('DELETE', path, { ...options, body }, false),
}

/** Full-page navigation endpoints (OAuth). */
export const socialRedirectUrl = (provider: 'google', next?: string) =>
  `${API_PREFIX}/auth/social/${provider}/redirect${next ? `?next=${encodeURIComponent(next)}` : ''}`
