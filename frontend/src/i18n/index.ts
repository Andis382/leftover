import { createI18n } from 'vue-i18n'
import en, { type MessageSchema } from './en'
import sq from './sq'
import { setApiLocale } from '@/lib/api'
import { setFormatLocale } from '@/lib/format'

export type Locale = 'en' | 'sq'

export const LOCALES: { code: Locale; label: string }[] = [
  { code: 'sq', label: 'Shqip' },
  { code: 'en', label: 'English' },
]

function initialLocale(): Locale {
  try {
    const saved = localStorage.getItem('locale')
    if (saved === 'en' || saved === 'sq') return saved
  } catch {
    /* storage may be blocked */
  }
  return navigator.language?.toLowerCase().startsWith('sq') ? 'sq' : 'en'
}

export const i18n = createI18n<[MessageSchema], Locale, false>({
  legacy: false,
  locale: initialLocale(),
  fallbackLocale: 'en',
  messages: { en, sq },
})

export function currentLocale(): Locale {
  return i18n.global.locale.value as Locale
}

export function setLocale(locale: Locale) {
  i18n.global.locale.value = locale
  try {
    localStorage.setItem('locale', locale)
  } catch {
    /* ignore */
  }
  document.documentElement.lang = locale
  setApiLocale(locale)
  setFormatLocale(locale)
}

// Apply once at startup so API calls and formatting agree with the UI.
setLocale(currentLocale())
