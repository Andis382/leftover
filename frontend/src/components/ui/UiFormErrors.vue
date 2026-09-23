<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import { PhWarningOctagon } from '@phosphor-icons/vue'

/**
 * Error summary at the top of a form. Each item links to its field; the summary takes
 * focus after a failed submit so keyboard and screen-reader users land on it.
 */
const props = defineProps<{
  errors: Record<string, string[]>
  message?: string | null
  /** field name -> input id; defaults to `f-<field>` */
  ids?: Record<string, string>
  /** bump to re-focus after each failed submit */
  trigger?: number
  labels?: Record<string, string>
}>()

const el = ref<HTMLElement | null>(null)

watch(
  () => props.trigger,
  async () => {
    await nextTick()
    el.value?.focus()
  },
)

function idFor(field: string) {
  return props.ids?.[field] ?? `f-${field}`
}

/** Messages don't repeat the field name, so the summary names the field from its <label>. */
function labelFor(field: string): string | null {
  if (props.labels?.[field]) return props.labels[field]
  const label = document.querySelector(`label[for="${idFor(field)}"]`)
  const text = label?.firstChild?.textContent?.trim()
  return text || null
}

function jump(field: string) {
  const target = document.getElementById(idFor(field))
  target?.focus()
  target?.scrollIntoView({ block: 'center', behavior: 'smooth' })
}
</script>

<template>
  <div
    v-if="Object.keys(errors).length || message"
    ref="el"
    class="summary"
    role="alert"
    tabindex="-1"
  >
    <PhWarningOctagon class="summary__icon" :size="22" weight="fill" aria-hidden="true" />
    <div>
      <p class="summary__title">
        {{ Object.keys(errors).length ? $t('errors.summaryTitle') : message }}
      </p>
      <ul v-if="Object.keys(errors).length" class="summary__list">
        <li v-for="(msgs, field) in errors" :key="field">
          <a :href="`#${idFor(String(field))}`" @click.prevent="jump(String(field))">
            <strong v-if="labelFor(String(field))">{{ labelFor(String(field)) }}:</strong>
            {{ msgs[0] }}
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.summary {
  display: flex;
  gap: 12px;
  padding: 14px 16px;
  background: var(--danger-soft);
  border: 1px solid color-mix(in srgb, var(--danger) 28%, transparent);
  border-radius: var(--radius);
  color: var(--danger-text);
}
.summary:focus-visible {
  outline: none;
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--danger) 22%, transparent);
}
.summary__icon {
  flex: none;
  margin-top: 1px;
}
.summary__title {
  font-weight: 700;
  font-size: var(--text-sm);
}
.summary__list {
  margin: 6px 0 0;
  padding-left: 18px;
  font-size: var(--text-sm);
}
.summary__list a {
  color: var(--danger-text);
}
</style>
