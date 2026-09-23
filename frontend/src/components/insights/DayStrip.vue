<script setup lang="ts">
import { computed } from 'vue'
import { PhCheck, PhMinus, PhX } from '@phosphor-icons/vue'
import { weekdayName } from '@/lib/dates'
import { formatDate } from '@/lib/format'
import type { Insights } from '@/types'

/** Five weeks of closings at a glance: counted, skipped on purpose, or simply missed. */
const props = defineProps<{ days: Insights['calendar'] }>()

const weeks = computed(() => {
  const rows: Insights['calendar'][] = []
  for (let i = 0; i < props.days.length; i += 7) rows.push(props.days.slice(i, i + 7))
  return rows
})
</script>

<template>
  <div class="strip">
    <div class="strip__grid" role="table" :aria-label="$t('insights.calendar')">
      <div class="strip__row strip__row--head" role="row">
        <span v-for="d in 7" :key="d" class="strip__weekday" role="columnheader">{{
          weekdayName(d, 'short')
        }}</span>
      </div>
      <div v-for="(week, i) in weeks" :key="i" class="strip__row" role="row">
        <span
          v-for="day in week"
          :key="day.date"
          class="cell"
          :class="`cell--${day.status.toLowerCase()}`"
          role="cell"
          :title="`${formatDate(day.date, 'medium')}: ${$t(`insights.status.${day.status}`)}`"
        >
          <span class="cell__day num">{{ Number(day.date.slice(8)) }}</span>
          <PhCheck
            v-if="day.status === 'COUNTED'"
            class="cell__icon"
            :size="11"
            weight="bold"
            aria-hidden="true"
          />
          <PhMinus
            v-else-if="day.status === 'SKIPPED'"
            class="cell__icon"
            :size="11"
            weight="bold"
            aria-hidden="true"
          />
          <PhX
            v-else-if="day.status === 'MISSED'"
            class="cell__icon"
            :size="11"
            weight="bold"
            aria-hidden="true"
          />
          <span class="visually-hidden">{{ $t(`insights.status.${day.status}`) }}</span>
        </span>
      </div>
    </div>
    <ul class="legend">
      <li v-for="s in ['COUNTED', 'SKIPPED', 'MISSED', 'CLOSED']" :key="s">
        <i class="cell" :class="`cell--${s.toLowerCase()}`" />{{ $t(`insights.status.${s}`) }}
      </li>
    </ul>
  </div>
</template>

<style scoped>
.strip__grid {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.strip__row {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 5px;
}
.strip__weekday {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  text-align: center;
  color: var(--text-subtle);
}
.cell {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 30px;
  padding: 0 7px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 650;
  border: 1px solid transparent;
}
.cell__day {
  opacity: 0.85;
}
.cell--counted {
  color: var(--success-text);
  background: var(--success-soft);
  border-color: color-mix(in srgb, var(--success) 22%, transparent);
}
.cell--skipped {
  color: var(--warning-text);
  background: repeating-linear-gradient(135deg, var(--warning-soft) 0 5px, var(--surface) 5px 9px);
  border-color: color-mix(in srgb, var(--warning) 35%, transparent);
}
.cell--missed {
  color: var(--danger-text);
  background: var(--surface);
  border: 1.5px dashed color-mix(in srgb, var(--danger) 55%, transparent);
}
.cell--closed {
  color: var(--text-subtle);
  background: var(--surface-sunken);
}
.cell--today {
  color: var(--primary-strong);
  background: var(--primary-soft);
  border: 1.5px solid var(--primary);
}
.cell--future {
  color: var(--gray-300);
  background: transparent;
  border: 1px dashed var(--border);
}
.legend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 14px;
  margin: 12px 0 0;
  padding: 0;
  list-style: none;
  font-size: var(--text-xs);
  color: var(--text-muted);
}
.legend li {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.legend .cell {
  display: inline-block;
  min-height: 0;
  width: 14px;
  height: 14px;
  padding: 0;
  border-radius: 4px;
}
</style>
