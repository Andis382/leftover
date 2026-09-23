/**
 * Albanian calendar words. Browsers without Albanian locale data (some headless and older
 * builds) print "2026 M09 24, Thu"; with these the dates read right everywhere.
 */

export const MONTHS = [
  'janar',
  'shkurt',
  'mars',
  'prill',
  'maj',
  'qershor',
  'korrik',
  'gusht',
  'shtator',
  'tetor',
  'nëntor',
  'dhjetor',
]

export const MONTHS_SHORT = [
  'jan',
  'shk',
  'mar',
  'pri',
  'maj',
  'qer',
  'korr',
  'gush',
  'sht',
  'tet',
  'nën',
  'dhj',
]

/** Sunday first, like Date.getDay() */
export const WEEKDAYS = [
  'e diel',
  'e hënë',
  'e martë',
  'e mërkurë',
  'e enjte',
  'e premte',
  'e shtunë',
]

export const WEEKDAYS_SHORT = ['Die', 'Hën', 'Mar', 'Mër', 'Enj', 'Pre', 'Sht']

export type RelativeUnit = 'second' | 'minute' | 'hour' | 'day' | 'week' | 'month' | 'year'

/** [one, many] in the form that follows "para" (ago) and "pas" (in): "para 1 ore", "para 3 orësh". */
const UNITS: Record<RelativeUnit, [string, string]> = {
  second: ['sekonde', 'sekondash'],
  minute: ['minute', 'minutash'],
  hour: ['ore', 'orësh'],
  day: ['dite', 'ditësh'],
  week: ['jave', 'javësh'],
  month: ['muaji', 'muajsh'],
  year: ['viti', 'vjetësh'],
}

/** "para 5 minutash", "pas 2 ditësh", "dje", "nesër", "tani". */
export function relative(value: number, unit: RelativeUnit): string {
  if (value === 0) return unit === 'day' ? 'sot' : 'tani'
  if (unit === 'day' && Math.abs(value) === 1) return value < 0 ? 'dje' : 'nesër'
  const n = Math.abs(value)
  const word = UNITS[unit][n === 1 ? 0 : 1]
  return value < 0 ? `para ${n} ${word}` : `pas ${n} ${word}`
}
