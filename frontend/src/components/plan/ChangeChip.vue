<script setup lang="ts">
import { computed } from 'vue'
import { PhArrowDown, PhArrowUp } from '@phosphor-icons/vue'
import { changeTone, formatChange } from '@/lib/plan'

/** The change against the same weekday last week: wheat for more, crust for fewer. */
const props = withDefaults(defineProps<{ change: number | null; size?: 'sm' | 'md' }>(), {
  size: 'md',
})

const tone = computed(() => changeTone(props.change))
</script>

<template>
  <span class="delta num" :class="[`delta--${tone}`, `delta--${size}`]">
    <PhArrowUp
      v-if="tone === 'more'"
      :size="size === 'sm' ? 11 : 13"
      weight="bold"
      aria-hidden="true"
    />
    <PhArrowDown
      v-else-if="tone === 'fewer'"
      :size="size === 'sm' ? 11 : 13"
      weight="bold"
      aria-hidden="true"
    />
    {{ tone === 'new' ? $t('plan.new') : formatChange(change) }}
  </span>
</template>

<style scoped>
.delta {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  min-width: 46px;
  justify-content: center;
  padding: 3px 9px;
  border-radius: var(--radius-pill);
  font-size: var(--text-sm);
  font-weight: 750;
  white-space: nowrap;
  border: 1px solid transparent;
}
.delta--sm {
  min-width: 40px;
  padding: 2px 7px;
  font-size: var(--text-xs);
}
.delta--more {
  color: var(--accent-soft-text);
  background: var(--accent-soft);
  border-color: color-mix(in srgb, var(--accent-500) 45%, transparent);
}
.delta--fewer {
  color: var(--primary-soft-text);
  background: var(--primary-soft);
  border-color: var(--primary-soft-border);
}
.delta--same,
.delta--new {
  color: var(--text-subtle);
  background: var(--surface-sunken);
}
</style>
