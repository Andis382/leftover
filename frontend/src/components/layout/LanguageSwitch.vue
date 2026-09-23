<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { LOCALES, setLocale, type Locale } from '@/i18n'

withDefaults(defineProps<{ inverse?: boolean }>(), { inverse: false })
const { locale } = useI18n()

function pick(code: Locale) {
  setLocale(code)
}
</script>

<template>
  <div
    class="lang"
    :class="{ 'lang--inverse': inverse }"
    role="group"
    :aria-label="$t('common.language')"
  >
    <button
      v-for="l in LOCALES"
      :key="l.code"
      type="button"
      class="lang__opt"
      :class="{ 'is-active': locale === l.code }"
      :aria-pressed="locale === l.code"
      :lang="l.code"
      :title="l.label"
      @click="pick(l.code)"
    >
      {{ l.code.toUpperCase() }}
    </button>
  </div>
</template>

<style scoped>
.lang {
  display: inline-flex;
  padding: 3px;
  gap: 2px;
  background: var(--surface-sunken);
  border: 1px solid var(--border);
  border-radius: var(--radius-pill);
}
.lang--inverse {
  background: rgb(255 255 255 / 0.08);
  border-color: rgb(255 255 255 / 0.14);
}
.lang__opt {
  min-width: 38px;
  height: 30px;
  padding: 0 8px;
  border: 0;
  border-radius: var(--radius-pill);
  background: transparent;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 750;
  letter-spacing: 0.04em;
}
.lang--inverse .lang__opt {
  color: var(--text-inverse-muted);
}
.lang__opt.is-active {
  background: var(--surface);
  color: var(--text);
  box-shadow: var(--shadow-sm);
}
.lang--inverse .lang__opt.is-active {
  background: rgb(255 255 255 / 0.92);
  color: var(--header-from);
}
.lang__opt:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px var(--focus-ring);
}
</style>
