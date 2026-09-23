<script setup lang="ts" generic="T extends string">
import type { Component } from 'vue'

const model = defineModel<T>({ required: true })

defineProps<{
  tabs: { value: T; label: string; icon?: Component; count?: number | null }[]
  label: string
}>()

function onKey(e: KeyboardEvent, tabs: { value: T }[], index: number) {
  if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return
  const next = (index + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length
  const tab = tabs[next]
  if (!tab) return
  model.value = tab.value
  const el = (e.currentTarget as HTMLElement).parentElement?.children[next] as
    HTMLElement | undefined
  el?.focus()
}
</script>

<template>
  <div class="tabs" role="tablist" :aria-label="label">
    <button
      v-for="(t, i) in tabs"
      :key="t.value"
      type="button"
      role="tab"
      class="tabs__tab"
      :class="{ 'is-active': model === t.value }"
      :aria-selected="model === t.value"
      :tabindex="model === t.value ? 0 : -1"
      @click="model = t.value"
      @keydown="onKey($event, tabs, i)"
    >
      <component :is="t.icon" v-if="t.icon" :size="18" weight="bold" aria-hidden="true" />
      <span>{{ t.label }}</span>
      <span v-if="t.count !== undefined && t.count !== null" class="tabs__count">{{
        t.count
      }}</span>
    </button>
  </div>
</template>

<style scoped>
.tabs {
  display: flex;
  gap: 4px;
  border-bottom: 1px solid var(--border);
  overflow-x: auto;
  scrollbar-width: none;
}
.tabs__tab {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 46px;
  padding: 0 14px;
  border: 0;
  background: transparent;
  color: var(--text-muted);
  font-size: var(--text-sm);
  font-weight: 650;
  white-space: nowrap;
  border-radius: var(--radius-sm) var(--radius-sm) 0 0;
}
.tabs__tab:hover {
  color: var(--text);
  background: var(--surface-hover);
}
.tabs__tab.is-active {
  color: var(--primary-strong);
}
.tabs__tab.is-active::after {
  content: '';
  position: absolute;
  left: 10px;
  right: 10px;
  bottom: -1px;
  height: 3px;
  border-radius: 3px 3px 0 0;
  background: linear-gradient(90deg, var(--brand-500), var(--brand-600));
}
.tabs__tab:focus-visible {
  outline: none;
  box-shadow: inset 0 0 0 3px var(--focus-ring);
}
.tabs__count {
  display: inline-grid;
  place-items: center;
  min-width: 22px;
  height: 20px;
  padding: 0 6px;
  font-size: 11px;
  font-weight: 700;
  border-radius: var(--radius-pill);
  background: var(--surface-sunken);
  color: var(--text-muted);
}
.tabs__tab.is-active .tabs__count {
  background: var(--primary-soft);
  color: var(--primary-soft-text);
}
</style>
