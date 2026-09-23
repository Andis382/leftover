<script setup lang="ts">
import { ref } from 'vue'
import { PhCheck, PhCopy } from '@phosphor-icons/vue'

const props = defineProps<{ value: string; label?: string }>()
const copied = ref(false)

async function copy() {
  try {
    await navigator.clipboard.writeText(props.value)
  } catch {
    const ta = document.createElement('textarea')
    ta.value = props.value
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    ta.remove()
  }
  copied.value = true
  setTimeout(() => (copied.value = false), 1800)
}
</script>

<template>
  <div class="copy">
    <input
      class="copy__value"
      :value="value"
      readonly
      :aria-label="label ?? value"
      @focus="($event.target as HTMLInputElement).select()"
    />
    <button type="button" class="copy__btn" :class="{ 'is-done': copied }" @click="copy">
      <component :is="copied ? PhCheck : PhCopy" :size="16" weight="bold" aria-hidden="true" />
      <span>{{ copied ? $t('common.copied') : $t('common.copy') }}</span>
    </button>
    <span class="visually-hidden" aria-live="polite">{{ copied ? $t('common.copied') : '' }}</span>
  </div>
</template>

<style scoped>
.copy {
  display: flex;
  align-items: stretch;
  min-width: 0;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
}
.copy__value {
  flex: 1;
  min-width: 0;
  padding: 0 12px;
  border: 0;
  background: transparent;
  font-family: var(--font-mono);
  font-size: var(--text-xs);
  color: var(--text-muted);
}
.copy__value:focus {
  outline: none;
}
.copy__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 42px;
  padding: 0 14px;
  border: 0;
  border-left: 1px solid var(--border);
  background: var(--surface);
  font-size: var(--text-sm);
  font-weight: 650;
  color: var(--primary-strong);
  white-space: nowrap;
}
.copy__btn:hover {
  background: var(--primary-soft);
}
.copy__btn.is-done {
  color: var(--success-text);
}
</style>
