<script setup lang="ts">
import { computed, type Component } from 'vue'
import { PhCheckCircle, PhInfo, PhWarning, PhWarningOctagon } from '@phosphor-icons/vue'

const props = withDefaults(
  defineProps<{
    tone?: 'info' | 'success' | 'warning' | 'danger'
    title?: string
    icon?: Component
  }>(),
  { tone: 'info', title: undefined, icon: undefined },
)

const defaultIcon = {
  info: PhInfo,
  success: PhCheckCircle,
  warning: PhWarning,
  danger: PhWarningOctagon,
}
const shown = computed(() => props.icon ?? defaultIcon[props.tone])
</script>

<template>
  <div class="notice" :class="`notice--${tone}`" :role="tone === 'danger' ? 'alert' : undefined">
    <component :is="shown" class="notice__icon" :size="20" weight="fill" aria-hidden="true" />
    <div class="notice__content">
      <p v-if="title" class="notice__title">{{ title }}</p>
      <div class="notice__body"><slot /></div>
    </div>
    <div v-if="$slots.actions" class="notice__actions"><slot name="actions" /></div>
  </div>
</template>

<style scoped>
.notice {
  --fg: var(--info-text);
  --bg: var(--info-soft);
  --ic: var(--info);
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: var(--bg);
  border: 1px solid color-mix(in srgb, var(--ic) 22%, transparent);
  border-radius: var(--radius);
  color: var(--fg);
}
.notice--success {
  --fg: var(--success-text);
  --bg: var(--success-soft);
  --ic: var(--success);
}
.notice--warning {
  --fg: var(--warning-text);
  --bg: var(--warning-soft);
  --ic: var(--warning);
}
.notice--danger {
  --fg: var(--danger-text);
  --bg: var(--danger-soft);
  --ic: var(--danger);
}
.notice__icon {
  flex: none;
  margin-top: 1px;
  color: var(--ic);
}
.notice__content {
  flex: 1;
  min-width: 0;
  font-size: var(--text-sm);
}
.notice__title {
  font-weight: 700;
  margin-bottom: 2px;
}
.notice__actions {
  display: flex;
  gap: 8px;
  flex: none;
  align-self: center;
}
@media (max-width: 640px) {
  .notice {
    flex-wrap: wrap;
  }
  .notice__actions {
    width: 100%;
  }
}
</style>
