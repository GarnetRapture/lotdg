import type { ZodType } from 'zod'

export class LotdgApiError extends Error {
  readonly statusCode: number

  readonly namespaceCode: string

  readonly labelPath: string

  readonly placeholderMap: Record<string, string | number>

  constructor(
    statusCode: number,
    namespaceCode: string,
    labelPath: string,
    placeholderMap: Record<string, string | number> = {},
  ) {
    super(`${namespaceCode}.${labelPath}`)
    this.name = 'LotdgApiError'
    this.statusCode = statusCode
    this.namespaceCode = namespaceCode
    this.labelPath = labelPath
    this.placeholderMap = placeholderMap
  }
}

const API_BASE_PATH = '/api'

async function parseResponse(response: Response): Promise<unknown> {
  const text = await response.text()

  if (text === '') {
    return {}
  }

  try {
    return JSON.parse(text) as unknown
  } catch {
    throw new LotdgApiError(response.status, 'system-message', 'error.unexpected-response')
  }
}

function toApiError(payload: unknown, statusCode: number): LotdgApiError {
  if (typeof payload === 'object' && payload !== null && 'error_label_path' in payload) {
    const errorPayload = payload as {
      error_namespace?: unknown
      error_label_path?: unknown
      error_placeholder?: unknown
    }

    return new LotdgApiError(
      statusCode,
      typeof errorPayload.error_namespace === 'string'
        ? errorPayload.error_namespace
        : 'system-message',
      typeof errorPayload.error_label_path === 'string'
        ? errorPayload.error_label_path
        : 'error.unknown',
      typeof errorPayload.error_placeholder === 'object' && errorPayload.error_placeholder !== null
        ? (errorPayload.error_placeholder as Record<string, string | number>)
        : {},
    )
  }

  return new LotdgApiError(statusCode, 'system-message', 'error.unknown')
}

async function request<TResult>(
  method: 'GET' | 'POST',
  path: string,
  schema: ZodType<TResult>,
  body?: Record<string, string | number | boolean>,
): Promise<TResult> {
  const requestInit: RequestInit = { method, headers: {} }

  if (body !== undefined) {
    const formData = new URLSearchParams()
    for (const [key, value] of Object.entries(body)) {
      formData.set(key, String(value))
    }
    requestInit.body = formData
    requestInit.headers = { 'Content-Type': 'application/x-www-form-urlencoded' }
  }

  const response = await fetch(`${API_BASE_PATH}${path}`, requestInit)
  const payload = await parseResponse(response)

  if (!response.ok) {
    throw toApiError(payload, response.status)
  }

  const parsed = schema.safeParse(payload)

  if (!parsed.success) {
    throw new LotdgApiError(response.status, 'system-message', 'error.unexpected-response')
  }

  return parsed.data
}

export function getJson<TResult>(path: string, schema: ZodType<TResult>): Promise<TResult> {
  return request('GET', path, schema)
}

export function postForm<TResult>(
  path: string,
  schema: ZodType<TResult>,
  body: Record<string, string | number | boolean> = {},
): Promise<TResult> {
  return request('POST', path, schema, body)
}
