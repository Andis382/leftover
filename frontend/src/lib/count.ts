import type { Category, CountItem, CountSummary } from '@/types'
import { minutesOf } from './dates'

/**
 * The closing count's local side: every change is queued (and kept in localStorage) until the
 * server has it, so a phone call, a dead battery or a lost connection mid-count loses nothing.
 */

export type PendingCount = {
  date: string
  productId: number
  left: number | null
  soldOutAt: string | null
  /** order of changes; a queued change is only removed if it was not changed again meanwhile */
  seq: number
}

export type CountQueue = Record<string, PendingCount>

export function countKey(date: string, productId: number): string {
  return `${date}:${productId}`
}

/** A newer value for the same product replaces the older one: only the latest is sent. */
export function enqueue(queue: CountQueue, change: Omit<PendingCount, 'seq'>): CountQueue {
  const seq = Math.max(0, ...Object.values(queue).map((c) => c.seq)) + 1
  return { ...queue, [countKey(change.date, change.productId)]: { ...change, seq } }
}

/** Drops a change the server has confirmed, unless it was changed again while in flight. */
export function settle(queue: CountQueue, sent: PendingCount): CountQueue {
  const key = countKey(sent.date, sent.productId)
  if (queue[key]?.seq !== sent.seq) return queue
  const rest = { ...queue }
  delete rest[key]
  return rest
}

/** The oldest change waiting to be sent. */
export function nextToSend(queue: CountQueue): PendingCount | null {
  return Object.values(queue).reduce<PendingCount | null>(
    (oldest, c) => (oldest === null || c.seq < oldest.seq ? c : oldest),
    null,
  )
}

export function pendingCount(queue: CountQueue, date?: string): number {
  return Object.values(queue).filter((c) => date === undefined || c.date === date).length
}

/** The server's rows with changes that have not reached it yet laid on top. */
export function withPending(items: CountItem[], queue: CountQueue, date: string): CountItem[] {
  return items.map((item) => {
    const pending = queue[countKey(date, item.productId)]
    return pending ? { ...item, left: pending.left, soldOutAt: pending.soldOutAt } : item
  })
}

/** Same numbers as the server's summary, computed locally so they follow every tap. */
export function summarize(items: CountItem[]): CountSummary {
  const summary: CountSummary = {
    counted: 0,
    total: items.length,
    bakedUnits: 0,
    soldUnits: 0,
    leftUnits: 0,
    wasteCents: 0,
    soldOut: 0,
  }
  for (const item of items) {
    if (item.left === null) continue
    summary.counted++
    summary.leftUnits += item.left
    summary.wasteCents += item.left * item.wasteValueCents
    if (item.soldOutAt !== null) summary.soldOut++
    if (item.baked !== null) {
      summary.bakedUnits += item.baked
      summary.soldUnits += Math.max(0, item.baked - item.left)
    }
  }
  return summary
}

/** Shelf order, grouped by category in the order the categories first appear on the shelf. */
export function groupByCategory<T extends { category: Category }>(
  items: T[],
): { category: Category; items: T[] }[] {
  const groups = new Map<Category, T[]>()
  for (const item of items) {
    const list = groups.get(item.category) ?? []
    list.push(item)
    groups.set(item.category, list)
  }
  return [...groups].map(([category, list]) => ({ category, items: list }))
}

export type SoldOutPresetKey = 'before10' | 'from10to12' | 'from12to15' | 'after15'

/** Rough times are enough: the forecast only needs to know how much of the day was left. */
export const SOLD_OUT_PRESETS: { key: SoldOutPresetKey; time: string; from: number }[] = [
  { key: 'before10', time: '09:30', from: 0 },
  { key: 'from10to12', time: '11:00', from: 600 },
  { key: 'from12to15', time: '13:30', from: 720 },
  { key: 'after15', time: '17:00', from: 900 },
]

/** The presets that make sense for a day's opening hours. */
export function presetsFor(opensAt: string | null, closesAt: string | null) {
  const opens = minutesOf(opensAt) ?? 0
  const closes = minutesOf(closesAt) ?? 24 * 60
  return SOLD_OUT_PRESETS.filter((p) => p.from < closes && (minutesOf(p.time) ?? 0) > opens)
}

export function presetOf(time: string | null): SoldOutPresetKey | null {
  return SOLD_OUT_PRESETS.find((p) => p.time === time)?.key ?? null
}
