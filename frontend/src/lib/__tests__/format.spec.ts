import { afterEach, describe, expect, it } from 'vitest'
import {
  centsToInput,
  formatDate,
  formatDateTime,
  formatPhone,
  formatRelative,
  formatTime,
  formatWeekday,
  initials,
  parseMoney,
  setFormatLocale,
  setFormatTimeZone,
} from '../format'

describe('Albanian dates, whatever the browser knows', () => {
  afterEach(() => {
    setFormatLocale('en')
    setFormatTimeZone(undefined)
  })

  it('spells dates out in Albanian', () => {
    setFormatLocale('sq')

    expect(formatDate('2026-09-24', 'long')).toBe('E enjte, 24 shtator 2026')
    expect(formatDate('2026-09-24')).toBe('24 sht 2026')
    expect(formatWeekday('2026-09-26', 'short')).toBe('Sht')
  })

  it('reads instants in the shop time zone on a 24-hour clock', () => {
    setFormatTimeZone('Europe/Tirane')
    setFormatLocale('sq')

    expect(formatTime('2026-09-23T18:41:00Z')).toBe('20:41')
    expect(formatDateTime('2026-09-23T02:00:00Z')).toBe('23 sht, 04:00')
  })

  it('says how long ago in Albanian', () => {
    setFormatLocale('sq')
    const now = new Date('2026-09-23T12:00:00Z')

    expect(formatRelative('2026-09-23T11:55:00Z', now)).toBe('para 5 minutash')
    expect(formatRelative('2026-09-23T11:00:00Z', now)).toBe('para 1 ore')
    expect(formatRelative('2026-09-22T12:00:00Z', now)).toBe('dje')
    expect(formatRelative('2026-09-20T12:00:00Z', now)).toBe('para 3 ditësh')
  })
})

describe('format helpers', () => {
  it('groups Albanian mobile numbers', () => {
    expect(formatPhone('355691234567')).toBe('+355 69 123 4567')
  })

  it('reads money the way people type it', () => {
    expect(parseMoney('12,50')).toBe(1250)
    expect(parseMoney('12.5')).toBe(1250)
    expect(parseMoney('')).toBeNull()
    expect(parseMoney('abc')).toBeNull()
  })

  it('round-trips cents to an input value', () => {
    expect(centsToInput(1250)).toBe('12.50')
    expect(centsToInput(1200)).toBe('12')
  })

  it('makes initials', () => {
    expect(initials('Arben Hoxha')).toBe('AH')
    expect(initials('Mira')).toBe('M')
  })
})
