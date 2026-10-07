/**
 * Mirrors the API error envelope {message, code, errors?}.
 */
export class ApiError extends Error {
  readonly status: number
  readonly code: string
  readonly errors: Record<string, string[]>

  constructor(status: number, code: string, message: string, errors: Record<string, string[]> = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.errors = errors
  }

  get isUnauthenticated(): boolean {
    return this.status === 401
  }

  get isValidation(): boolean {
    return this.status === 422
  }

  /** First message per field, keyed by field name (dot paths kept). */
  fieldErrors(): Record<string, string> {
    return Object.fromEntries(Object.entries(this.errors).map(([field, messages]) => [field, messages[0] ?? '']))
  }
}

export class NetworkError extends Error {
  constructor() {
    super('Network request failed')
    this.name = 'NetworkError'
  }
}
