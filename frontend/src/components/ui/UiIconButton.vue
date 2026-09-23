<script setup lang="ts">
import { computed, type Component } from 'vue'
import { RouterLink, type RouteLocationRaw } from 'vue-router'

const props = withDefaults(
  defineProps<{
    icon: Component
    /** Required: icon-only controls need an accessible name. */
    label: string
    variant?: 'ghost' | 'secondary' | 'inverse' | 'soft'
    size?: 'sm' | 'md'
    to?: RouteLocationRaw
    href?: string
    target?: string
    disabled?: boolean
  }>(),
  { variant: 'ghost', size: 'md', to: undefined, href: undefined, target: undefined },
)

const tag = computed(() => (props.to ? RouterLink : props.href ? 'a' : 'button'))
</script>

<template>
  <component
    :is="tag"
    class="icon-btn"
    :class="[`icon-btn--${variant}`, `icon-btn--${size}`]"
    :to="to"
    :href="href"
    :target="target"
    :rel="target === '_blank' ? 'noopener' : undefined"
    :type="!to && !href ? 'button' : undefined"
    :disabled="!to && !href ? disabled : undefined"
    :aria-label="label"
    :title="label"
  >
    <component :is="icon" :size="size === 'sm' ? 18 : 20" weight="bold" aria-hidden="true" />
  </component>
</template>

<style scoped>
.icon-btn {
  display: inline-grid;
  place-items: center;
  width: 44px;
  height: 44px;
  flex: none;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  background: transparent;
  cursor: pointer;
  transition:
    background-color var(--duration) var(--ease),
    color var(--duration) var(--ease),
    transform var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease);
}
.icon-btn--sm {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-xs);
}
.icon-btn:hover:not(:disabled) {
  color: var(--text);
  background: var(--surface-sunken);
}
.icon-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 4px var(--focus-ring);
}
.icon-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.icon-btn--secondary {
  background: var(--surface);
  border-color: var(--border-strong);
  box-shadow: var(--shadow-xs), var(--highlight);
}
.icon-btn--secondary:hover:not(:disabled) {
  background: var(--surface);
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}
.icon-btn--soft {
  color: var(--primary-soft-text);
  background: var(--primary-soft);
}
.icon-btn--inverse {
  color: var(--header-text);
  background: rgb(255 255 255 / 0.1);
  border-color: rgb(255 255 255 / 0.14);
}
.icon-btn--inverse:hover:not(:disabled) {
  color: var(--header-text);
  background: rgb(255 255 255 / 0.2);
}
</style>
