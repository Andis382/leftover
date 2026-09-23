/**
 * Thin fetch wrapper for the same-origin JSON API.
 * - sends the session cookie and echoes XSRF-TOKEN as X-XSRF-TOKEN on unsafe requests
 * - asks the server to answer in the UI language (Accept-Language)
 * - turns error bodies ({message, errors}) into ApiError
 */

export type FieldErrors = Record<string, string[]>

export class ApiError extends Error {
  readonly status: number
  readonly errors: FieldErrors

  constructor(status: number, message: string, errors: FieldErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }

  get isValidation() {
    return this.status === 422
  }
}

type Options = {
  /** Don't trigger the global "signed out" handler on 401 (used by the session probe). */
  allow401?: boolean
  signal?: AbortSignal
}

let currentLocale = 'en'
let onUnauthorized: () => void = () => {}

export function setApiLocale(locale: string) {
  currentLocale = locale
}

export function setUnauthorizedHandler(handler: () => void) {
  onUnauthorized = handler
}

function readCookie(name: string): string | null {
  const match = document.cookie.split('; ').find((c) => c.startsWith(name + '='))
  return match ? decodeURIComponent(match.slice(name.length + 1)) : null
}

let csrfPromise: Promise<void> | null = null

/** Makes sure an XSRF-TOKEN cookie exists (after boot, sign-in and sign-out it may not). */
export function refreshCsrf(): Promise<void> {
  csrfPromise ??= fetch('/api/auth/csrf', { credentials: 'same-origin' })
    .then(() => undefined)
    .finally(() => {
      csrfPromise = null
    })
  return csrfPromise
}

async function request<T>(
  method: string,
  url: string,
  body?: unknown,
  options: Options = {},
  retried = false,
): Promise<T> {
  const unsafe = method !== 'GET' && method !== 'HEAD'
  if (unsafe && !readCookie('XSRF-TOKEN')) {
    await refreshCsrf()
  }
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Accept-Language': currentLocale,
    'X-Requested-With': 'XMLHttpRequest',
  }
  const isForm = typeof FormData !== 'undefined' && body instanceof FormData
  if (body !== undefined && !isForm) {
    headers['Content-Type'] = 'application/json'
  }
  if (unsafe) {
    const token = readCookie('XSRF-TOKEN')
    if (token) headers['X-XSRF-TOKEN'] = token
  }

  let response: Response
  try {
    response = await fetch('/api' + url, {
      method,
      headers,
      body: body === undefined ? undefined : isForm ? (body as FormData) : JSON.stringify(body),
      credentials: 'same-origin',
      signal: options.signal,
    })
  } catch (e) {
    if ((e as Error).name === 'AbortError') throw e
    throw new ApiError(0, 'network')
  }

  // A stale CSRF token (e.g. right after signing in) gets one silent retry.
  if ((response.status === 403 || response.status === 419) && unsafe && !retried) {
    const text = await response.clone().text()
    if (response.status === 419 || /csrf/i.test(text) || text === '') {
      await refreshCsrf()
      return request<T>(method, url, body, options, true)
    }
  }

  if (response.status === 204) return undefined as T
  const isJson = response.headers.get('content-type')?.includes('application/json')
  const data = isJson ? await response.json() : undefined

  if (!response.ok) {
    if (response.status === 401 && !options.allow401) onUnauthorized()
    const message = (data && (data.message as string)) || response.statusText || 'error'
    throw new ApiError(response.status, message, (data && data.errors) || {})
  }
  return data as T
}

export const api = {
  get: <T>(url: string, options?: Options) => request<T>('GET', url, undefined, options),
  post: <T>(url: string, body?: unknown, options?: Options) =>
    request<T>('POST', url, body ?? {}, options),
  put: <T>(url: string, body?: unknown, options?: Options) =>
    request<T>('PUT', url, body ?? {}, options),
  patch: <T>(url: string, body?: unknown, options?: Options) =>
    request<T>('PATCH', url, body ?? {}, options),
  delete: <T = void>(url: string, options?: Options) =>
    request<T>('DELETE', url, undefined, options),
  upload: <T>(url: string, form: FormData, options?: Options) =>
    request<T>('POST', url, form, options),
}

/** Builds a query string, skipping empty values. */
export function query(
  params: Record<string, string | number | boolean | null | undefined>,
): string {
  const entries = Object.entries(params).filter(
    ([, v]) => v !== undefined && v !== null && v !== '',
  )
  if (!entries.length) return ''
  return '?' + new URLSearchParams(entries.map(([k, v]) => [k, String(v)])).toString()
}
