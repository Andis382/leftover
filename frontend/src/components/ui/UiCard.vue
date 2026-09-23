<script setup lang="ts">
import { computed, type Component } from 'vue'
import { RouterLink, type RouteLocationRaw } from 'vue-router'

const props = withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    icon?: Component
    to?: RouteLocationRaw
    padding?: 'none' | 'sm' | 'md' | 'lg'
    tone?: 'default' | 'muted' | 'primary' | 'warning' | 'danger'
    as?: string
  }>(),
  {
    title: undefined,
    subtitle: undefined,
    icon: undefined,
    to: undefined,
    padding: 'md',
    tone: 'default',
    as: 'section',
  },
)

const tag = computed(() => (props.to ? RouterLink : props.as))
</script>

<template>
  <component
    :is="tag"
    :to="to"
    class="card"
    :class="[`card--pad-${padding}`, `card--${tone}`, { 'card--link': !!to }]"
  >
    <header v-if="title || $slots.header || $slots.actions" class="card__header">
      <slot name="header">
        <div class="card__heading">
          <span v-if="icon" class="card__icon"
            ><component :is="icon" :size="20" weight="duotone" aria-hidden="true"
          /></span>
          <div class="card__titles">
            <h2 class="card__title">{{ title }}</h2>
            <p v-if="subtitle" class="card__subtitle">{{ subtitle }}</p>
          </div>
        </div>
      </slot>
      <div v-if="$slots.actions" class="card__actions"><slot name="actions" /></div>
    </header>
    <div class="card__body"><slot /></div>
    <footer v-if="$slots.footer" class="card__footer"><slot name="footer" /></footer>
  </component>
</template>

<style scoped>
.card {
  --pad: 20px;
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm), var(--highlight);
  color: inherit;
  text-decoration: none;
}
.card--pad-none {
  --pad: 0px;
}
.card--pad-sm {
  --pad: 14px;
}
.card--pad-lg {
  --pad: 28px;
}
@media (max-width: 640px) {
  .card {
    --pad: 16px;
  }
  .card--pad-lg {
    --pad: 20px;
  }
  .card--pad-none {
    --pad: 0px;
  }
}
.card--muted {
  background: var(--surface-muted);
  box-shadow: var(--highlight);
}
.card--primary {
  background: linear-gradient(180deg, var(--primary-soft), var(--surface) 70%);
  border-color: var(--primary-soft-border);
}
.card--warning {
  background: linear-gradient(180deg, var(--warning-soft), var(--surface) 75%);
  border-color: color-mix(in srgb, var(--warning) 25%, transparent);
}
.card--danger {
  background: linear-gradient(180deg, var(--danger-soft), var(--surface) 75%);
  border-color: color-mix(in srgb, var(--danger) 25%, transparent);
}
.card--link {
  cursor: pointer;
  transition:
    transform var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease),
    border-color var(--duration) var(--ease);
}
.card--link:hover {
  color: inherit;
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg), var(--highlight);
  border-color: var(--border-strong);
}
.card--link:focus-visible {
  outline: none;
  box-shadow:
    var(--shadow-md),
    0 0 0 4px var(--focus-ring);
}
.card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: var(--pad) var(--pad) 0;
}
.card--pad-none .card__header {
  padding: 18px 20px 14px;
  border-bottom: 1px solid var(--border);
}
.card__heading {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}
.card__icon {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  flex: none;
  color: var(--primary);
  background: var(--primary-soft);
  border: 1px solid var(--primary-soft-border);
  border-radius: var(--radius-sm);
}
.card__titles {
  min-width: 0;
}
.card__title {
  font-size: var(--text-md);
  font-weight: 700;
  letter-spacing: -0.01em;
}
.card__subtitle {
  margin-top: 2px;
  font-size: var(--text-sm);
  color: var(--text-muted);
}
.card__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: none;
}
.card__body {
  padding: var(--pad);
  flex: 1;
  min-width: 0;
}
.card__footer {
  padding: 14px var(--pad);
  border-top: 1px solid var(--border);
  background: var(--surface-muted);
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}
</style>
