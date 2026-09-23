import { ref } from 'vue'
import { defineStore } from 'pinia'

export type Toast = {
  id: number
  tone: 'success' | 'error' | 'info'
  title: string
  text?: string
}

let nextId = 1

/** Short confirmations ("Saved") and failures. Announced to screen readers by UiToaster. */
export const useToasts = defineStore('toasts', () => {
  const items = ref<Toast[]>([])

  function push(toast: Omit<Toast, 'id'>, timeout = 4200) {
    const id = nextId++
    items.value = [...items.value, { ...toast, id }].slice(-4)
    if (timeout > 0) setTimeout(() => dismiss(id), timeout)
    return id
  }

  function dismiss(id: number) {
    items.value = items.value.filter((t) => t.id !== id)
  }

  const success = (title: string, text?: string) => push({ tone: 'success', title, text })
  const error = (title: string, text?: string) => push({ tone: 'error', title, text }, 7000)
  const info = (title: string, text?: string) => push({ tone: 'info', title, text })

  return { items, push, dismiss, success, error, info }
})
