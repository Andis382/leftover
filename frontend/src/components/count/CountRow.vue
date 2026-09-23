<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { PhCheckCircle, PhClockCountdown } from '@phosphor-icons/vue'
import UiStepper from '@/components/ui/UiStepper.vue'
import UiChip from '@/components/ui/UiChip.vue'
import { presetOf } from '@/lib/count'
import { formatTime } from '@/lib/format'
import type { CountItem } from '@/types'

const props = defineProps<{ item: CountItem; locked?: boolean }>()
const emit = defineEmits<{
  change: [left: number | null, soldOutAt: string | null]
  soldOut: []
  next: []
}>()

const { t } = useI18n()

const counted = computed(() => props.item.left !== null)
const soldOutLabel = computed(() => {
  const time = props.item.soldOutAt
  if (!time) return t('count.soldOut')
  const preset = presetOf(time)
  return t('count.soldOutAt', { time: preset ? t(`count.presets.${preset}`) : time })
})

function setLeft(left: number | null) {
  emit('change', left, left === 0 ? props.item.soldOutAt : null)
}
</script>

<template>
  <article class="row" :class="{ 'is-counted': counted, 'is-soldout': !!item.soldOutAt }">
    <div class="row__info">
      <h3 class="row__name">{{ item.name }}</h3>
      <p class="row__meta">
        <span class="num">{{
          item.baked === null ? $t('count.bakedUnknown') : $t('count.baked', { n: item.baked })
        }}</span>
        <span v-if="item.countedAt" class="row__who">
          <PhCheckCircle :size="14" weight="fill" aria-hidden="true" />
          {{ formatTime(item.countedAt)
          }}<template v-if="item.countedBy"> · {{ item.countedBy.split(' ')[0] }}</template>
        </span>
      </p>
    </div>
    <UiStepper
      :id="`left-${item.productId}`"
      class="row__stepper"
      size="lg"
      :model-value="item.left"
      :max="item.baked ?? 10000"
      :label="$t('count.leftLabel', { name: item.name })"
      :disabled="locked"
      enterkeyhint="next"
      @update:model-value="setLeft"
      @enter="emit('next')"
    />
    <div class="row__quick">
      <UiChip
        tone="success"
        :pressed="item.left === 0 && !item.soldOutAt"
        :disabled="locked"
        @click="emit('change', 0, null)"
      >
        {{ $t('count.zeroLeft') }}
      </UiChip>
      <UiChip
        tone="accent"
        :icon="PhClockCountdown"
        :pressed="!!item.soldOutAt"
        :disabled="locked"
        @click="emit('soldOut')"
      >
        {{ soldOutLabel }}
      </UiChip>
    </div>
  </article>
</template>

<style scoped>
.row {
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 10px 14px;
  padding: 14px 16px 14px 18px;
  transition: background-color var(--duration) var(--ease);
}
.row::before {
  /* a slim marker on the left edge: empty, counted, or sold out */
  content: '';
  position: absolute;
  left: 0;
  top: 14px;
  bottom: 14px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: var(--gray-200);
  transition: background-color var(--duration) var(--ease);
}
.row.is-counted::before {
  background: var(--success);
}
.row.is-soldout::before {
  background: var(--accent-500);
}
.row:focus-within {
  background: var(--surface-hover);
}
.row__info {
  min-width: 0;
}
.row__name {
  font-family: var(--font-body);
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  line-height: 1.25;
  overflow-wrap: anywhere;
}
.row__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 12px;
  margin-top: 3px;
  font-size: var(--text-sm);
  color: var(--text-subtle);
}
.row__who {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--success-text);
  font-weight: 600;
}
.row__quick {
  grid-column: 1 / -1;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
@media (min-width: 720px) {
  .row {
    grid-template-columns: minmax(0, 1fr) auto auto;
  }
  .row__quick {
    grid-column: 2;
    grid-row: 1;
    justify-content: flex-end;
  }
  .row__stepper {
    grid-column: 3;
    grid-row: 1;
  }
}
</style>
