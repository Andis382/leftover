import type { Component } from 'vue'
import { PhBasket, PhBread, PhCake, PhCookie, PhPizza } from '@phosphor-icons/vue'
import type { Category } from '@/types'

export const CATEGORY_ICONS: Record<Category, Component> = {
  BREAD: PhBread,
  PASTRY: PhCookie,
  SAVORY: PhPizza,
  SWEET: PhCake,
  OTHER: PhBasket,
}
