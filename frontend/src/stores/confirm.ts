import { ref } from 'vue'
import { defineStore } from 'pinia'

export type ConfirmRequest = {
  title: string
  text?: string
  confirmLabel?: string
  cancelLabel?: string
  danger?: boolean
}

/** Promise-based confirmation dialog: `if (await confirm.ask({...})) ...` */
export const useConfirm = defineStore('confirm', () => {
  const current = ref<ConfirmRequest | null>(null)
  let resolver: ((ok: boolean) => void) | null = null

  function ask(request: ConfirmRequest): Promise<boolean> {
    resolver?.(false)
    current.value = request
    return new Promise((resolve) => {
      resolver = resolve
    })
  }

  function answer(ok: boolean) {
    resolver?.(ok)
    resolver = null
    current.value = null
  }

  return { current, ask, answer }
})
