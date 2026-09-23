import { describe, expect, it } from 'vitest'
import { addDays, defaultPlanDay, isDay, isoWeekday, minutesOf, shopNow } from '../dates'
import { changeTone, confidenceBars, formatChange, planTotals, trays } from '../plan'
import type { PlanRow } from '@/types'

describe('shop days', () => {
  it('adds days across months and daylight saving', () => {
    expect(addDays('2026-09-30', 1)).toBe('2026-10-01')
    expect(addDays('2026-10-25', 1)).toBe('2026-10-26')
    expect(addDays('2026-03-01', -1)).toBe('2026-02-28')
  })

  it('numbers weekdays the ISO way', () => {
    expect(isoWeekday('2026-09-21')).toBe(1)
    expect(isoWeekday('2026-09-27')).toBe(7)
  })

  it('reads the date and time in the shop time zone, not the browser one', () => {
    // 23:30 UTC on 21 September is already 01:30 on the 22nd in Tirana
    const now = new Date('2026-09-21T23:30:00Z')

    expect(shopNow('Europe/Tirane', now)).toEqual({ day: '2026-09-22', minutes: 90 })
  })

  it("shows today's plan in the morning and tomorrow's from noon", () => {
    expect(defaultPlanDay('Europe/Tirane', new Date('2026-09-22T02:00:00Z'))).toBe('2026-09-22')
    expect(defaultPlanDay('Europe/Tirane', new Date('2026-09-22T10:00:00Z'))).toBe('2026-09-23')
  })

  it('validates route dates and clock times', () => {
    expect(isDay('2026-09-22')).toBe(true)
    expect(isDay('22.09.2026')).toBe(false)
    expect(minutesOf('10:30')).toBe(630)
    expect(minutesOf(null)).toBeNull()
  })
})

describe('plan helpers', () => {
  it('writes changes with a real minus sign', () => {
    expect(formatChange(12)).toBe('+12')
    expect(formatChange(-6)).toBe('−6')
    expect(formatChange(0)).toBe('±0')
    expect(formatChange(null)).toBe('')
    expect([changeTone(3), changeTone(-1), changeTone(0), changeTone(null)]).toEqual([
      'more',
      'fewer',
      'same',
      'new',
    ])
  })

  it('maps confidence to bars and rounds trays up', () => {
    expect(confidenceBars('medium')).toBe(2)
    expect(trays(25, 12)).toBe(3)
    expect(trays(24, 12)).toBe(2)
    expect(trays(5, 0)).toBe(5)
  })

  it('totals what will be baked', () => {
    const row = (willBake: number, traySize: number, unitPriceCents: number) =>
      ({ willBake, traySize, unitPriceCents }) as PlanRow

    expect(planTotals([row(24, 12, 70), row(10, 8, 120)])).toEqual({
      units: 34,
      trays: 4,
      valueCents: 2880,
    })
  })
})
