import type { Confidence, PlanRow } from '@/types'

/** "+12", "−6" (a real minus sign), or "±0". */
export function formatChange(change: number | null): string {
  if (change === null) return ''
  if (change === 0) return '±0'
  return change > 0 ? `+${change}` : `−${Math.abs(change)}`
}

export function changeTone(change: number | null): 'more' | 'fewer' | 'same' | 'new' {
  if (change === null) return 'new'
  if (change === 0) return 'same'
  return change > 0 ? 'more' : 'fewer'
}

export function confidenceBars(confidence: Confidence): number {
  return { low: 1, medium: 2, high: 3 }[confidence]
}

/** Whole trays needed for a quantity. */
export function trays(quantity: number, traySize: number): number {
  return Math.ceil(quantity / Math.max(1, traySize))
}

/** The same totals the server sends, recomputed after a local change to "will bake". */
export function planTotals(rows: PlanRow[]) {
  return rows.reduce(
    (t, r) => {
      t.units += r.willBake
      t.trays += trays(r.willBake, r.traySize)
      t.valueCents += r.willBake * r.unitPriceCents
      return t
    },
    { units: 0, trays: 0, valueCents: 0 },
  )
}
