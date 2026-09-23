<script setup lang="ts" generic="T extends string | number | boolean | null">
import type { Component } from 'vue'

const model = defineModel<T>()

withDefaults(
  defineProps<{
    options: { value: T; label: string; icon?: Component }[]
    /** Accessible name for the group */
    label: string
    size?: 'md' | 'lg'
    block?: boolean
  }>(),
  { size: 'md' },
)
</script>

<template>
  <div
    class="seg"
    :class="[`seg--${size}`, { 'seg--block': block }]"
    role="radiogroup"
    :aria-label="label"
  >
    <button
      v-for="o in options"
      :key="String(o.value)"
      type="button"
      role="radio"
      class="seg__opt"
      :class="{ 'is-active': model === o.value }"
      :aria-checked="model === o.value"
      @click="model = o.value"
    >
      <component :is="o.icon" v-if="o.icon" :size="18" weight="bold" aria-hidden="true" />
      <span>{{ o.label }}</span>
    </button>
  </div>
</template>

<style scoped>
.seg {
  display: inline-flex;
  gap: 4px;
  padding: 4px;
  background: var(--surface-sunken);
  border: 1px solid var(--border);
  border-radius: calc(var(--radius-sm) + 4px);
  box-shadow: inset 0 1px 2px rgb(16 24 40 / 0.06);
  max-width: 100%;
  overflow-x: auto;
}
.seg--block {
  display: flex;
}
.seg--block .seg__opt {
  flex: 1;
}
.seg__opt {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 38px;
  padding: 0 14px;
  border: 0;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--text-muted);
  font-size: var(--text-sm);
  font-weight: 600;
  white-space: nowrap;
  transition:
    background-color var(--duration) var(--ease),
    color var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease);
}
.seg--lg .seg__opt {
  min-height: 46px;
  font-size: var(--text-md);
}
.seg__opt:hover {
  color: var(--text);
}
.seg__opt.is-active {
  color: var(--text);
  background: var(--surface);
  box-shadow: var(--shadow-sm), var(--highlight);
}
.seg__opt:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px var(--focus-ring);
}
</style>
