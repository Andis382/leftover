import { formatWeekday } from './format'

/**
 * Shop days as "YYYY-MM-DD" strings. Arithmetic happens at local noon so a daylight-saving
 * change can never move a day, and "today" is always the shop's today, in its time zone.
 */

export function parseDay(day: string): Date {
  return new Date(day + 'T12:00:00')
}

export function toDay(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

export function addDays(day: string, days: number): string {
  const date = parseDay(day)
  date.setDate(date.getDate() + days)
  return toDay(date)
}

/** 1 = Monday … 7 = Sunday */
export function isoWeekday(day: string): number {
  return parseDay(day).getDay() || 7
}

/** The shop's date and minute of the day, in its own time zone. */
export function shopNow(
  timeZone?: string,
  now: Date = new Date(),
): { day: string; minutes: number } {
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(now)
  const part = (type: Intl.DateTimeFormatPartTypes) =>
    parts.find((p) => p.type === type)?.value ?? '00'
  return {
    day: `${part('year')}-${part('month')}-${part('day')}`,
    minutes: Number(part('hour')) * 60 + Number(part('minute')),
  }
}

/** The plan that matters now: before noon today's is being baked, after noon it is tomorrow's. */
export function defaultPlanDay(timeZone?: string, now: Date = new Date()): string {
  const { day, minutes } = shopNow(timeZone, now)
  return minutes < 12 * 60 ? day : addDays(day, 1)
}

/** Weekday name for an ISO weekday, in the interface language. */
export function weekdayName(weekday: number, style: 'short' | 'long' = 'long'): string {
  // 21 September 2026 is a Monday
  return formatWeekday(addDays('2026-09-21', weekday - 1), style)
}

export function isDay(value: unknown): value is string {
  return (
    typeof value === 'string' &&
    /^\d{4}-\d{2}-\d{2}$/.test(value) &&
    !Number.isNaN(parseDay(value).getTime())
  )
}

/** "10:30" to minutes after midnight. */
export function minutesOf(time: string | null | undefined): number | null {
  const match = time?.match(/^(\d{1,2}):(\d{2})/)
  return match ? Number(match[1]) * 60 + Number(match[2]) : null
}
