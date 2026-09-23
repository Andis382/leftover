<script setup lang="ts">
import type { Component } from 'vue'

withDefaults(
  defineProps<{
    tone?: 'neutral' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'accent'
    icon?: Component
    dot?: boolean
    size?: 'sm' | 'md'
  }>(),
  { tone: 'neutral', icon: undefined, size: 'md' },
)
</script>

<template>
  <span class="badge" :class="[`badge--${tone}`, `badge--${size}`]">
    <span v-if="dot" class="badge__dot" aria-hidden="true" />
    <component
      :is="icon"
      v-else-if="icon"
      :size="size === 'sm' ? 12 : 14"
      weight="bold"
      aria-hidden="true"
    />
    <slot />
  </span>
</template>

<style scoped>
.badge {
  --fg: var(--text-muted);
  --bg: var(--surface-sunken);
  --bd: var(--border);
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 26px;
  padding: 0 10px;
  font-size: var(--text-xs);
  font-weight: 650;
  line-height: 1;
  white-space: nowrap;
  color: var(--fg);
  background: var(--bg);
  border: 1px solid var(--bd);
  border-radius: var(--radius-pill);
}
.badge--sm {
  height: 22px;
  padding: 0 8px;
  font-size: 11px;
}
.badge__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: currentColor;
  box-shadow: 0 0 0 3px color-mix(in srgb, currentColor 18%, transparent);
}
.badge--primary {
  --fg: var(--primary-soft-text);
  --bg: var(--primary-soft);
  --bd: var(--primary-soft-border);
}
.badge--success {
  --fg: var(--success-text);
  --bg: var(--success-soft);
  --bd: color-mix(in srgb, var(--success) 22%, transparent);
}
.badge--warning {
  --fg: var(--warning-text);
  --bg: var(--warning-soft);
  --bd: color-mix(in srgb, var(--warning) 24%, transparent);
}
.badge--danger {
  --fg: var(--danger-text);
  --bg: var(--danger-soft);
  --bd: color-mix(in srgb, var(--danger) 22%, transparent);
}
.badge--info {
  --fg: var(--info-text);
  --bg: var(--info-soft);
  --bd: color-mix(in srgb, var(--info) 20%, transparent);
}
.badge--accent {
  --fg: var(--accent-soft-text);
  --bg: var(--accent-soft);
  --bd: color-mix(in srgb, var(--accent) 25%, transparent);
}
</style>
