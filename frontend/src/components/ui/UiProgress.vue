<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    value: number
    max?: number
    label: string
    tone?: 'primary' | 'success' | 'warning' | 'danger'
  }>(),
  {
    max: 100,
    tone: 'primary',
  },
)

const pct = computed(() => Math.max(0, Math.min(100, (props.value / (props.max || 1)) * 100)))
</script>

<template>
  <div
    class="progress"
    :class="`progress--${tone}`"
    role="progressbar"
    :aria-label="label"
    :aria-valuenow="value"
    aria-valuemin="0"
    :aria-valuemax="max"
  >
    <span class="progress__bar" :style="{ width: `${pct}%` }" />
  </div>
</template>

<style scoped>
.progress {
  --c: var(--primary);
  height: 8px;
  background: var(--surface-sunken);
  border-radius: var(--radius-pill);
  box-shadow: inset 0 1px 2px rgb(16 24 40 / 0.08);
  overflow: hidden;
}
.progress--success {
  --c: var(--success);
}
.progress--warning {
  --c: var(--warning);
}
.progress--danger {
  --c: var(--danger);
}
.progress__bar {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, color-mix(in srgb, var(--c) 75%, #fff), var(--c));
  transition: width 400ms var(--ease);
}
</style>
