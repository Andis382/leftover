/** Shapes of the API's JSON (camelCase). Money is integer cents, dates are "YYYY-MM-DD". */

export type Category = 'BREAD' | 'PASTRY' | 'SAVORY' | 'SWEET' | 'OTHER'

export const CATEGORIES: Category[] = ['BREAD', 'PASTRY', 'SAVORY', 'SWEET', 'OTHER']

export type Product = {
  id: number
  name: string
  category: Category
  unitPriceCents: number
  unitCostCents: number | null
  traySize: number
  /** the usual quantity for each weekday, Monday first */
  baselines: number[]
  /** ISO weekdays, 1 = Monday */
  activeWeekdays: number[]
  shelfOrder: number
  active: boolean
  archivedAt: string | null
}

export type DayStatus = 'OPEN' | 'COUNTED' | 'SKIPPED'

export type CountItem = {
  productId: number
  name: string
  category: Category
  baked: number | null
  left: number | null
  soldOutAt: string | null
  countedAt: string | null
  countedBy: string | null
  unitPriceCents: number
  wasteValueCents: number
}

export type CountSummary = {
  counted: number
  total: number
  bakedUnits: number
  soldUnits: number
  leftUnits: number
  wasteCents: number
  soldOut: number
}

export type CountSheet = {
  date: string
  weekday: number
  isToday: boolean
  shopOpen: boolean
  opensAt: string | null
  closesAt: string | null
  status: DayStatus
  closedAt: string | null
  closedBy: string | null
  skipReason: string | null
  items: CountItem[]
  summary: CountSummary
  previous: { date: string; status: DayStatus; counted: number } | null
}

export type Confidence = 'low' | 'medium' | 'high'

export type PlanRow = {
  productId: number
  name: string
  category: Category
  traySize: number
  unitPriceCents: number
  suggested: number
  willBake: number
  overridden: boolean
  lastBaked: number | null
  change: number | null
  reasonCode: string
  reason: string
  confidence: Confidence
  observations: number
}

export type Plan = {
  date: string
  weekday: number
  shopOpen: boolean
  isToday: boolean
  isPast: boolean
  generated: boolean
  generatedAt: string | null
  generatedBy: string | null
  finalAt: string
  sentAt: string | null
  sentTo: string | null
  bakedConfirmedAt: string | null
  bakedConfirmedBy: string | null
  headline: string
  rows: PlanRow[]
  totals: {
    units: number
    trays: number
    valueCents: number
    more: number
    fewer: number
    same: number
  }
  notBaked: { productId: number; name: string }[]
}

export type PlanMessagePreview = {
  phone: string | null
  name: string | null
  locale: string
  body: string
  waUrl: string | null
}

export type PeriodTotals = {
  bakedUnits: number
  soldUnits: number
  leftUnits: number
  wasteCents: number
  soldOuts: number
  missedCents: number
  countedDays: number
  wastePct: number | null
}

export type InsightDayStatus = 'COUNTED' | 'SKIPPED' | 'MISSED' | 'CLOSED' | 'TODAY' | 'FUTURE'

export type Insights = {
  days: number
  from: string
  to: string
  current: PeriodTotals
  previous: PeriodTotals
  daily: { date: string; status: InsightDayStatus; sold: number; left: number; soldOuts: number }[]
  weekly: {
    weekStart: string
    bakedUnits: number
    leftUnits: number
    wasteCents: number
    wastePct: number | null
  }[]
  products: {
    productId: number
    name: string
    category: Category
    countedDays: number
    avgLeft: number
    wastePct: number | null
    soldOutDays: number
    wasteCents: number
    previousAvgLeft: number | null
    trend: 'down' | 'up' | 'flat' | 'new'
  }[]
  /** before is null for sell-outs in the last hour before closing */
  patterns: {
    productId: number
    weekday: number
    times: number
    of: number
    before: string | null
    text: string
  }[]
  sellouts: { date: string; time: string; productId: number; name: string }[]
  calendar: { date: string; status: InsightDayStatus }[]
}

export type ShopHours = {
  weekday: number
  open: boolean
  opensAt: string | null
  closesAt: string | null
}

export type ShopSettings = {
  hours: ShopHours[]
  planTime: string
  countReminderOffset: number
  planPhone: string | null
  timezone: string
}

export type Automation = {
  demo: boolean
  timezone: string
  today: string
  openToday: boolean
  planTime: string
  planAt: string
  planGeneratedAt: string | null
  planSentAt: string | null
  planRecipient: string | null
  reminderAt: string | null
  reminderSentAt: string | null
  countStatus: DayStatus
  reminderRecipients: { name: string; phone: string }[]
  outcome?: string
}
