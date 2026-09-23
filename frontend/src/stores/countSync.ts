import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api, ApiError } from '@/lib/api'
import {
  enqueue,
  nextToSend,
  pendingCount,
  settle,
  type CountQueue,
  type PendingCount,
} from '@/lib/count'
import type { CountItem } from '@/types'
import { useAuth } from './auth'

type Listener = {
  saved?: (date: string, item: CountItem) => void
  rejected?: (change: PendingCount, message: string) => void
}

const RETRY_MS = 6000
const DEBOUNCE_MS = 350

/**
 * Autosave for the closing count. Each change goes into a queue kept in localStorage and is
 * sent as its own PATCH; if the phone is offline the queue waits and is sent when it is back.
 * Lives in a store so saving carries on while the person moves to another screen.
 */
export const useCountSync = defineStore('countSync', () => {
  const auth = useAuth()
  const queue = ref<CountQueue>({})
  const state = ref<'idle' | 'saving' | 'offline'>('idle')
  const listeners = new Set<Listener>()
  let loadedFor: string | null = null
  let flushing = false
  let debounce: ReturnType<typeof setTimeout> | undefined
  let retry: ReturnType<typeof setTimeout> | undefined

  const waiting = computed(() => pendingCount(queue.value))

  function storageKey() {
    return `leftover:count-queue:${auth.user?.id ?? 'guest'}`
  }

  /** Picks up what an earlier visit (or a closed tab) left unsent. */
  function load() {
    const key = storageKey()
    if (loadedFor === key) return
    loadedFor = key
    try {
      queue.value = JSON.parse(localStorage.getItem(key) ?? '{}') as CountQueue
    } catch {
      queue.value = {}
    }
  }

  function persist() {
    try {
      localStorage.setItem(storageKey(), JSON.stringify(queue.value))
    } catch {
      /* storage full or blocked: the change still goes out while the page is open */
    }
  }

  function record(date: string, productId: number, left: number | null, soldOutAt: string | null) {
    load()
    queue.value = enqueue(queue.value, { date, productId, left, soldOutAt })
    persist()
    clearTimeout(debounce)
    debounce = setTimeout(flush, DEBOUNCE_MS)
  }

  async function flush() {
    if (flushing || !auth.signedIn) return
    load()
    flushing = true
    clearTimeout(retry)
    try {
      for (let change = nextToSend(queue.value); change; change = nextToSend(queue.value)) {
        state.value = 'saving'
        const sent = change
        try {
          const item = await api.patch<CountItem>(`/count/${sent.date}/items/${sent.productId}`, {
            left: sent.left,
            soldOutAt: sent.soldOutAt,
          })
          queue.value = settle(queue.value, sent)
          persist()
          listeners.forEach((l) => l.saved?.(sent.date, item))
        } catch (e) {
          if (
            !(e instanceof ApiError) ||
            e.status === 0 ||
            e.status === 401 ||
            e.status === 419 ||
            e.status >= 500
          ) {
            // not the value's fault: keep it and try again later
            state.value = 'offline'
            retry = setTimeout(flush, RETRY_MS)
            return
          }
          queue.value = settle(queue.value, sent)
          persist()
          listeners.forEach((l) => l.rejected?.(sent, e.errors.left?.[0] ?? e.message))
        }
      }
      state.value = 'idle'
    } finally {
      flushing = false
    }
  }

  function listen(listener: Listener): () => void {
    listeners.add(listener)
    return () => listeners.delete(listener)
  }

  if (typeof window !== 'undefined') {
    window.addEventListener('online', () => flush())
  }

  return { queue, state, waiting, load, record, flush, listen }
})
