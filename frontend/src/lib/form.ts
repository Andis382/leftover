import { reactive, ref, type Ref } from 'vue'
import { ApiError, type FieldErrors } from './api'

/**
 * Form state with server-side validation errors.
 *
 *   const form = useForm({ name: '', phone: '' })
 *   await form.submit(() => api.post('/units', form.data))
 *
 * On a 422 the field errors land in form.errors and the summary focuses itself.
 */
export function useForm<T extends Record<string, unknown>>(initial: T) {
  const data = reactive({ ...initial }) as T
  const errors: Ref<FieldErrors> = ref({})
  const message = ref<string | null>(null)
  const processing = ref(false)
  const submitted = ref(0)

  function reset(values: Partial<T> = {}) {
    Object.assign(data, { ...initial, ...values })
    errors.value = {}
    message.value = null
  }

  function fill(values: Partial<T>) {
    Object.assign(data, values)
  }

  function error(field: string): string | undefined {
    return errors.value[field]?.[0]
  }

  async function submit<R>(action: () => Promise<R>): Promise<R | undefined> {
    processing.value = true
    errors.value = {}
    message.value = null
    try {
      return await action()
    } catch (e) {
      if (e instanceof ApiError) {
        errors.value = e.errors
        message.value = e.status === 0 ? null : e.message
        submitted.value++
        if (e.status === 0) throw e
        return undefined
      }
      throw e
    } finally {
      processing.value = false
    }
  }

  return { data, errors, message, processing, submitted, reset, fill, error, submit }
}
