import { describe, expect, it } from 'vitest'
import {
  countKey,
  enqueue,
  groupByCategory,
  nextToSend,
  pendingCount,
  presetOf,
  presetsFor,
  settle,
  summarize,
  withPending,
} from '../count'
import type { CountItem } from '@/types'

function item(productId: number, over: Partial<CountItem> = {}): CountItem {
  return {
    productId,
    name: `P${productId}`,
    category: 'BREAD',
    baked: 30,
    left: null,
    soldOutAt: null,
    countedAt: null,
    countedBy: null,
    unitPriceCents: 80,
    wasteValueCents: 30,
    ...over,
  }
}

describe('count queue', () => {
  it('keeps only the latest value per product and day', () => {
    let q = enqueue({}, { date: '2026-09-21', productId: 1, left: 3, soldOutAt: null })
    q = enqueue(q, { date: '2026-09-21', productId: 1, left: 4, soldOutAt: null })
    q = enqueue(q, { date: '2026-09-21', productId: 2, left: 0, soldOutAt: '11:00' })

    expect(pendingCount(q)).toBe(2)
    expect(q[countKey('2026-09-21', 1)]?.left).toBe(4)
  })

  it('sends the oldest change first', () => {
    let q = enqueue({}, { date: '2026-09-21', productId: 7, left: 1, soldOutAt: null })
    q = enqueue(q, { date: '2026-09-21', productId: 3, left: 2, soldOutAt: null })

    expect(nextToSend(q)?.productId).toBe(7)
    expect(nextToSend({})).toBeNull()
  })

  it('drops a confirmed change but keeps one that changed again in flight', () => {
    const q = enqueue({}, { date: '2026-09-21', productId: 1, left: 3, soldOutAt: null })
    const sent = nextToSend(q)!
    const changedMeanwhile = enqueue(q, {
      date: '2026-09-21',
      productId: 1,
      left: 5,
      soldOutAt: null,
    })

    expect(pendingCount(settle(q, sent))).toBe(0)
    expect(settle(changedMeanwhile, sent)[countKey('2026-09-21', 1)]?.left).toBe(5)
  })

  it('lays unsent values over what the server knows, for that day only', () => {
    const q = enqueue({}, { date: '2026-09-21', productId: 2, left: 0, soldOutAt: '10:30' })
    const items = [item(1, { left: 4 }), item(2)]

    const shown = withPending(items, q, '2026-09-21')

    expect(shown[0]?.left).toBe(4)
    expect(shown[1]).toMatchObject({ left: 0, soldOutAt: '10:30' })
    expect(withPending(items, q, '2026-09-22')[1]?.left).toBeNull()
    expect(pendingCount(q, '2026-09-22')).toBe(0)
  })
})

describe('summarize', () => {
  it('counts only counted products and never reads a blank as zero', () => {
    const summary = summarize([
      item(1, { left: 4 }),
      item(2, { left: 0, soldOutAt: '12:10' }),
      item(3),
      item(4, { left: 2, baked: null }),
    ])

    expect(summary).toEqual({
      counted: 3,
      total: 4,
      bakedUnits: 60,
      soldUnits: 56,
      leftUnits: 6,
      wasteCents: 180,
      soldOut: 1,
    })
  })
})

describe('groupByCategory', () => {
  it('follows the shelf: groups in order of first appearance', () => {
    const groups = groupByCategory([
      item(1),
      item(2, { category: 'PASTRY' }),
      item(3),
      item(4, { category: 'SWEET' }),
    ])

    expect(groups.map((g) => g.category)).toEqual(['BREAD', 'PASTRY', 'SWEET'])
    expect(groups[0]?.items.map((i) => i.productId)).toEqual([1, 3])
  })
})

describe('sell-out presets', () => {
  it('offers only times inside the opening hours', () => {
    expect(presetsFor('06:30', '20:30').map((p) => p.key)).toEqual([
      'before10',
      'from10to12',
      'from12to15',
      'after15',
    ])
    expect(presetsFor('07:00', '14:00').map((p) => p.key)).toEqual([
      'before10',
      'from10to12',
      'from12to15',
    ])
  })

  it('recognises a preset time', () => {
    expect(presetOf('11:00')).toBe('from10to12')
    expect(presetOf('11:05')).toBeNull()
  })
})
