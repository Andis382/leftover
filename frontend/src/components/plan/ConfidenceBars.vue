<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { confidenceBars } from '@/lib/plan'
import type { Confidence } from '@/types'

/** Three bars: how many counted days the suggestion stands on. */
const props = defineProps<{ confidence: Confidence; observations: number }>()
const { t } = useI18n()

const filled = computed(() => confidenceBars(props.confidence))
const label = computed(
  () =>
    `${t(`plan.confidence.${props.confidence}`)} · ${t('plan.confidence.days', { n: props.observations }, props.observations)}`,
)
</script>

<template>
  <span class="conf" :class="`conf--${confidence}`" :title="label">
    <span
      v-for="i in 3"
      :key="i"
      class="conf__bar"
      :class="{ 'is-on': i <= filled }"
      :style="{ height: `${5 + i * 3}px` }"
      aria-hidden="true"
    />
    <span class="visually-hidden">{{ label }}</span>
  </span>
</template>

<style scoped>
.conf {
  display: inline-flex;
  align-items: flex-end;
  gap: 2px;
  height: 14px;
}
.conf__bar {
  width: 4px;
  border-radius: 2px;
  background: var(--gray-200);
}
.conf--low .is-on {
  background: var(--warning);
}
.conf--medium .is-on {
  background: var(--accent-600);
}
.conf--high .is-on {
  background: var(--success);
}
</style>
