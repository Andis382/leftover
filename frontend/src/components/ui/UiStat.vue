<script setup lang="ts">
import { computed, type Component } from 'vue'
import { RouterLink, type RouteLocationRaw } from 'vue-router'

const props = withDefaults(
  defineProps<{
    label: string
    value: string | number
    hint?: string
    icon?: Component
    tone?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'neutral'
    to?: RouteLocationRaw
    trend?: 'up' | 'down' | null
  }>(),
  { hint: undefined, icon: undefined, tone: 'primary', to: undefined, trend: null },
)

const tag = computed(() => (props.to ? RouterLink : 'div'))
</script>

<template>
  <component :is="tag" :to="to" class="stat" :class="[`stat--${tone}`, { 'stat--link': !!to }]">
    <div class="stat__top">
      <span class="stat__label">{{ label }}</span>
      <span v-if="icon" class="stat__icon"
        ><component :is="icon" :size="20" weight="duotone" aria-hidden="true"
      /></span>
    </div>
    <div class="stat__value num">{{ value }}</div>
    <div v-if="hint || $slots.hint" class="stat__hint" :class="trend ? `stat__hint--${trend}` : ''">
      <slot name="hint">{{ hint }}</slot>
    </div>
  </component>
</template>

<style scoped>
.stat {
  --tone: var(--primary);
  --tone-soft: var(--primary-soft);
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 18px 20px;
  min-width: 0;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm), var(--highlight);
  color: inherit;
  text-decoration: none;
  overflow: hidden;
}
.stat::before {
  content: '';
  position: absolute;
  inset: 0 0 auto 0;
  height: 3px;
  background: linear-gradient(90deg, var(--tone), color-mix(in srgb, var(--tone) 30%, transparent));
  opacity: 0.85;
}
.stat--success {
  --tone: var(--success);
  --tone-soft: var(--success-soft);
}
.stat--warning {
  --tone: var(--warning);
  --tone-soft: var(--warning-soft);
}
.stat--danger {
  --tone: var(--danger);
  --tone-soft: var(--danger-soft);
}
.stat--info {
  --tone: var(--info);
  --tone-soft: var(--info-soft);
}
.stat--neutral {
  --tone: var(--gray-400);
  --tone-soft: var(--surface-sunken);
}
.stat--link {
  transition:
    transform var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease);
}
.stat--link:hover {
  color: inherit;
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg), var(--highlight);
}
.stat__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.stat__label {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-muted);
}
.stat__icon {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  color: var(--tone);
  background: var(--tone-soft);
  border-radius: var(--radius-sm);
}
.stat__value {
  font-family: var(--font-display);
  font-size: var(--text-3xl);
  font-weight: 750;
  letter-spacing: -0.03em;
  line-height: 1.05;
}
.stat__hint {
  font-size: var(--text-xs);
  font-weight: 550;
  color: var(--text-subtle);
}
.stat__hint--up {
  color: var(--success-text);
}
.stat__hint--down {
  color: var(--danger-text);
}
</style>
