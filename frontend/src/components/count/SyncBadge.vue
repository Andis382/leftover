<script setup lang="ts">
import { computed } from 'vue'
import { PhCloudArrowUp, PhCloudCheck, PhCloudSlash } from '@phosphor-icons/vue'
import UiBadge from '@/components/ui/UiBadge.vue'

/** Where the count stands: all saved, saving, or waiting for a connection. */
const props = defineProps<{ state: 'idle' | 'saving' | 'offline'; waiting: number }>()

const view = computed(() => {
  if (props.state === 'offline' || (props.state === 'idle' && props.waiting > 0)) {
    return { tone: 'warning' as const, icon: PhCloudSlash, key: 'count.sync.offline' }
  }
  if (props.state === 'saving')
    return { tone: 'neutral' as const, icon: PhCloudArrowUp, key: 'count.sync.saving' }
  return { tone: 'success' as const, icon: PhCloudCheck, key: 'count.sync.saved' }
})
</script>

<template>
  <span class="sync" role="status" aria-live="polite">
    <UiBadge :tone="view.tone" :icon="view.icon">{{ $t(view.key, { n: waiting }) }}</UiBadge>
  </span>
</template>

<style scoped>
.sync {
  display: inline-flex;
}
</style>
