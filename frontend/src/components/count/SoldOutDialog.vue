<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { PhClock } from '@phosphor-icons/vue'
import UiDialog from '@/components/ui/UiDialog.vue'
import UiChip from '@/components/ui/UiChip.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiField from '@/components/ui/UiField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { presetOf, presetsFor } from '@/lib/count'
import type { CountItem } from '@/types'

const open = defineModel<boolean>('open', { default: false })
const props = defineProps<{
  item: CountItem | null
  opensAt: string | null
  closesAt: string | null
}>()
const emit = defineEmits<{ pick: [time: string | null] }>()

const exact = ref('')
const presets = computed(() => presetsFor(props.opensAt, props.closesAt))
const current = computed(() => props.item?.soldOutAt ?? null)

watch(open, (isOpen) => {
  if (isOpen) exact.value = current.value && !presetOf(current.value) ? current.value : ''
})

function pick(time: string | null) {
  emit('pick', time)
  open.value = false
}
</script>

<template>
  <UiDialog
    v-model:open="open"
    size="sm"
    :title="$t('count.soldOutQuestion', { name: item?.name ?? '' })"
    :description="$t('count.soldOutHint')"
  >
    <div class="stack">
      <div class="presets" role="group" :aria-label="$t('count.soldOut')">
        <UiChip
          v-for="p in presets"
          :key="p.key"
          size="lg"
          tone="accent"
          :pressed="current === p.time"
          @click="pick(p.time)"
        >
          {{ $t(`count.presets.${p.key}`) }}
        </UiChip>
      </div>
      <form class="exact" novalidate @submit.prevent="exact && pick(exact)">
        <UiField id="f-soldout-time" :label="$t('count.exactTime')">
          <template #default="{ id }">
            <UiInput
              :id="id"
              v-model="exact"
              type="time"
              :icon="PhClock"
              :min="opensAt ?? undefined"
              :max="closesAt ?? undefined"
            />
          </template>
        </UiField>
        <UiButton type="submit" variant="secondary" :disabled="!exact">{{
          $t('count.setTime')
        }}</UiButton>
      </form>
    </div>
    <template v-if="current" #footer>
      <UiButton variant="ghost" @click="pick(null)">{{ $t('count.clearSoldOut') }}</UiButton>
    </template>
  </UiDialog>
</template>

<style scoped>
.presets {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}
.presets :deep(.chip) {
  justify-content: center;
}
.exact {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: end;
  gap: 10px;
}
</style>
