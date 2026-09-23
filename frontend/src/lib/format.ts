import {
  MONTHS,
  MONTHS_SHORT,
  WEEKDAYS,
  WEEKDAYS_SHORT,
  relative,
  type RelativeUnit,
} from './albanian'

/**
 * Locale-aware formatting. Albanian dates are spelled out from our own word lists (browsers
 * without Albanian data print "2026 M09 24"); times are 24-hour in both languages, as a shop
 * reads them.
 */

let locale = 'en'
let currency = 'EUR'
let timeZone: string | undefined

// Albanian writes numbers like French: 1 234,5 and 12,50 €. Used where the browser has no "sq".
const numberTagSq = Intl.NumberFormat.supportedLocalesOf(['sq-AL']).length ? 'sq-AL' : 'fr-FR'

export function setFormatLocale(next: string) {
  locale = next
}

export function setFormatCurrency(next: string) {
  currency = next
}

export function setFormatTimeZone(next: string | undefined) {
  timeZone = next
}

function tag() {
  return locale === 'sq' ? numberTagSq : 'en-GB'
}

function toDate(value: string | number | Date): Date {
  if (value instanceof Date) return value
  // Plain dates ("2026-09-23") are calendar days, not instants: read them at local noon.
  if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value))
    return new Date(value + 'T12:00:00')
  return new Date(value)
}

type Parts = {
  year: number
  month: number
  day: number
  weekday: number
  hour: string
  minute: string
}

/** Calendar parts of an instant in the shop's time zone (plain dates stay in local time). */
function calendarParts(value: string | number | Date): Parts {
  const got = new Intl.DateTimeFormat('en-GB', {
    timeZone: isPlainDate(value) ? undefined : timeZone,
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
    weekday: 'short',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(toDate(value))
  const part = (type: Intl.DateTimeFormatPartTypes) => got.find((p) => p.type === type)?.value ?? ''
  return {
    year: Number(part('year')),
    month: Number(part('month')) - 1,
    day: Number(part('day')),
    weekday: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(part('weekday')),
    hour: part('hour'),
    minute: part('minute'),
  }
}

function capitalize(text: string) {
  return text.charAt(0).toUpperCase() + text.slice(1)
}

export function formatDate(
  value: string | number | Date | null | undefined,
  style: 'short' | 'medium' | 'long' = 'medium',
) {
  if (value === null || value === undefined || value === '') return '—'
  if (locale === 'sq') {
    const p = calendarParts(value)
    if (style === 'short') return `${p.day} ${MONTHS_SHORT[p.month]}`
    if (style === 'long')
      return capitalize(`${WEEKDAYS[p.weekday]}, ${p.day} ${MONTHS[p.month]} ${p.year}`)
    return `${p.day} ${MONTHS_SHORT[p.month]} ${p.year}`
  }
  const options: Intl.DateTimeFormatOptions =
    style === 'short'
      ? { day: 'numeric', month: 'short' }
      : style === 'long'
        ? { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
        : { day: 'numeric', month: 'short', year: 'numeric' }
  return new Intl.DateTimeFormat('en-GB', {
    ...options,
    timeZone: isPlainDate(value) ? undefined : timeZone,
  }).format(toDate(value))
}

export function formatDateTime(value: string | number | Date | null | undefined) {
  if (value === null || value === undefined || value === '') return '—'
  const p = calendarParts(value)
  return `${formatDate(value, 'short')}, ${p.hour}:${p.minute}`
}

export function formatTime(value: string | number | Date | null | undefined) {
  if (value === null || value === undefined || value === '') return '—'
  if (typeof value === 'string' && /^\d{2}:\d{2}/.test(value)) return value.slice(0, 5)
  const p = calendarParts(value)
  return `${p.hour}:${p.minute}`
}

export function formatWeekday(value: string | Date, style: 'short' | 'long' = 'long') {
  if (locale === 'sq') {
    const day = toDate(value).getDay()
    return style === 'short' ? (WEEKDAYS_SHORT[day] ?? '') : (WEEKDAYS[day] ?? '')
  }
  return new Intl.DateTimeFormat('en-GB', { weekday: style }).format(toDate(value))
}

export function formatMonth(value: string | Date) {
  if (locale === 'sq') {
    const d = toDate(value)
    return capitalize(`${MONTHS[d.getMonth()]} ${d.getFullYear()}`)
  }
  return new Intl.DateTimeFormat('en-GB', { month: 'long', year: 'numeric' }).format(toDate(value))
}

/** "3 days ago", "in 2 weeks" (Albanian: "para 3 ditësh", "pas 2 javësh") */
export function formatRelative(
  value: string | number | Date | null | undefined,
  now: Date = new Date(),
) {
  if (value === null || value === undefined || value === '') return '—'
  const diffSeconds = Math.round((toDate(value).getTime() - now.getTime()) / 1000)
  const abs = Math.abs(diffSeconds)
  const [amount, unit]: [number, RelativeUnit] =
    abs < 60
      ? [diffSeconds, 'second']
      : abs < 3600
        ? [Math.round(diffSeconds / 60), 'minute']
        : abs < 86400
          ? [Math.round(diffSeconds / 3600), 'hour']
          : abs < 86400 * 7
            ? [Math.round(diffSeconds / 86400), 'day']
            : abs < 86400 * 45
              ? [Math.round(diffSeconds / (86400 * 7)), 'week']
              : abs < 86400 * 365
                ? [Math.round(diffSeconds / (86400 * 30)), 'month']
                : [Math.round(diffSeconds / (86400 * 365)), 'year']
  if (locale === 'sq') return relative(amount, unit)
  return new Intl.RelativeTimeFormat('en-GB', { numeric: 'auto' }).format(amount, unit)
}

/** Money is always carried as integer cents. */
export function formatMoney(
  cents: number | null | undefined,
  options: { currency?: string; decimals?: boolean } = {},
) {
  if (cents === null || cents === undefined) return '—'
  const decimals = options.decimals ?? cents % 100 !== 0
  return new Intl.NumberFormat(tag(), {
    style: 'currency',
    currency: options.currency ?? currency,
    minimumFractionDigits: decimals ? 2 : 0,
    maximumFractionDigits: decimals ? 2 : 0,
  }).format(cents / 100)
}

export function formatNumber(value: number | null | undefined, maximumFractionDigits = 1) {
  if (value === null || value === undefined || Number.isNaN(value)) return '—'
  return new Intl.NumberFormat(tag(), { maximumFractionDigits }).format(value)
}

export function formatPercent(value: number | null | undefined, maximumFractionDigits = 0) {
  if (value === null || value === undefined || Number.isNaN(value)) return '—'
  return new Intl.NumberFormat(tag(), { style: 'percent', maximumFractionDigits }).format(value)
}

/** "355691234567" -> "+355 69 123 4567" (Albanian mobile grouping, generic otherwise) */
export function formatPhone(value: string | null | undefined) {
  if (!value) return '—'
  const digits = value.replace(/\D/g, '')
  if (digits.startsWith('355') && digits.length === 12) {
    return `+355 ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8)}`
  }
  if (digits.startsWith('383') && digits.length === 11) {
    return `+383 ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8)}`
  }
  return '+' + digits.replace(/(\d{3})(?=\d)/g, '$1 ').trim()
}

/** Parse "12,50" or "12.50" typed by a person into cents. */
export function parseMoney(input: string | number | null | undefined): number | null {
  if (input === null || input === undefined || input === '') return null
  if (typeof input === 'number') return Math.round(input * 100)
  const normalized = input.replace(/\s/g, '').replace(',', '.')
  const n = Number(normalized)
  return Number.isFinite(n) ? Math.round(n * 100) : null
}

export function centsToInput(cents: number | null | undefined): string {
  if (cents === null || cents === undefined) return ''
  return (cents / 100).toFixed(2).replace(/\.00$/, '')
}

export function todayIso(): string {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

export function initials(name: string | null | undefined) {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  return (
    (parts[0]?.[0] ?? '') + (parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '')
  ).toUpperCase()
}

function isPlainDate(value: unknown) {
  return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)
}
