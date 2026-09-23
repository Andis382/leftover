<script setup lang="ts">
import type { Component } from 'vue'

/** A pill that toggles (aria-pressed) or acts, e.g. "0 left" and "Sold out" on the count. */
withDefaults(
  defineProps<{
    pressed?: boolean
    icon?: Component
    tone?: 'neutral' | 'primary' | 'accent' | 'success'
    size?: 'md' | 'lg'
    disabled?: boolean
  }>(),
  { pressed: false, icon: undefined, tone: 'neutral', size: 'md' },
)

defineEmits<{ click: [MouseEvent] }>()
</script>

<template>
  <button
    type="button"
    class="chip"
    :class="[`chip--${tone}`, `chip--${size}`, { 'is-pressed': pressed }]"
    :aria-pressed="pressed"
    :disabled="disabled"
    @click="$emit('click', $event)"
  >
    <component
      :is="icon"
      v-if="icon"
      :size="size === 'lg' ? 18 : 16"
      :weight="pressed ? 'fill' : 'bold'"
      aria-hidden="true"
    />
    <span><slot /></span>
  </button>
</template>

<style scoped>
.chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 36px;
  padding: 0 12px;
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-pill);
  background: var(--surface);
  color: var(--text-muted);
  font-size: var(--text-sm);
  font-weight: 650;
  white-space: nowrap;
  box-shadow: var(--shadow-xs), var(--highlight);
  transition:
    background-color var(--duration) var(--ease),
    border-color var(--duration) var(--ease),
    color var(--duration) var(--ease),
    transform var(--duration) var(--ease);
}
.chip--lg {
  min-height: var(--tap);
  padding: 0 16px;
  font-size: var(--text-md);
}
.chip:hover:not(:disabled) {
  color: var(--text);
  border-color: var(--gray-400);
  transform: translateY(-1px);
}
.chip:active:not(:disabled) {
  transform: translateY(0);
}
.chip:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.chip:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px var(--focus-ring);
}
.chip.is-pressed {
  box-shadow: inset 0 1px 2px rgb(60 30 10 / 0.12);
}
.chip--neutral.is-pressed {
  color: var(--text);
  background: var(--surface-sunken);
  border-color: var(--gray-400);
}
.chip--primary.is-pressed {
  color: var(--primary-soft-text);
  background: var(--primary-soft);
  border-color: var(--brand-300);
}
.chip--accent.is-pressed {
  color: var(--accent-soft-text);
  background: var(--accent-soft);
  border-color: var(--accent-400);
}
.chip--success.is-pressed {
  color: var(--success-text);
  background: var(--success-soft);
  border-color: color-mix(in srgb, var(--success) 40%, transparent);
}
</style>
