import type { Component } from 'vue'
import {
  PhBasket,
  PhBread,
  PhChartBar,
  PhChatCircleDots,
  PhGearSix,
  PhListChecks,
  PhSunHorizon,
} from '@phosphor-icons/vue'

export type NavItem = {
  /** route name */
  name: string
  /** i18n key for the label */
  label: string
  icon: Component
  /** shown in the phone's bottom bar (max 4); others go under "More" */
  primary?: boolean
  /** false: left out of the desktop pill nav (reachable from the account menu) */
  topBar?: boolean
  roles?: string[]
}

const OWNER = ['OWNER']

export const NAV: NavItem[] = [
  { name: 'count', label: 'nav.count', icon: PhListChecks, primary: true },
  { name: 'plan', label: 'nav.plan', icon: PhBread, primary: true },
  { name: 'morning', label: 'nav.morning', icon: PhSunHorizon, primary: true, roles: OWNER },
  { name: 'insights', label: 'nav.insights', icon: PhChartBar, primary: true, roles: OWNER },
  { name: 'products', label: 'nav.products', icon: PhBasket, roles: OWNER },
  { name: 'messages', label: 'nav.messages', icon: PhChatCircleDots, roles: OWNER },
  { name: 'settings', label: 'nav.settings', icon: PhGearSix, topBar: false },
]
