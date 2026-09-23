import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api, refreshCsrf } from '@/lib/api'
import { setFormatCurrency, setFormatTimeZone } from '@/lib/format'
import { setLocale, type Locale } from '@/i18n'

export type User = {
  id: number
  name: string
  email: string
  role: string
  locale: Locale
  phone: string | null
}

export type Organization = {
  id: number
  name: string
  phone: string | null
  country: string
  locale: Locale
  timezone: string
  currency: string
}

export type Me = {
  user: User
  organization: Organization
  demo: boolean
}

export const useAuth = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const organization = ref<Organization | null>(null)
  const demo = ref(false)
  const ready = ref(false)

  const signedIn = computed(() => user.value !== null)

  function apply(me: Me) {
    user.value = me.user
    organization.value = me.organization
    demo.value = me.demo
    setFormatCurrency(me.organization.currency)
    setFormatTimeZone(me.organization.timezone)
  }

  function clear() {
    user.value = null
    organization.value = null
  }

  async function load() {
    try {
      apply(await api.get<Me>('/auth/me', { allow401: true }))
    } catch {
      clear()
    } finally {
      ready.value = true
    }
  }

  async function login(email: string, password: string) {
    const me = await api.post<Me>('/auth/login', { email, password })
    apply(me)
    if (me.user.locale) setLocale(me.user.locale)
    await refreshCsrf()
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    organizationName: string
    locale: Locale
  }) {
    apply(await api.post<Me>('/auth/register', payload))
    await refreshCsrf()
  }

  async function join(payload: {
    token: string
    name: string
    email: string
    password: string
    locale: Locale
  }) {
    apply(await api.post<Me>('/auth/join', payload))
    await refreshCsrf()
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } finally {
      clear()
      await refreshCsrf()
    }
  }

  function hasRole(...roles: string[]) {
    return !!user.value && roles.includes(user.value.role)
  }

  return {
    user,
    organization,
    demo,
    ready,
    signedIn,
    apply,
    clear,
    load,
    login,
    register,
    join,
    logout,
    hasRole,
  }
})
