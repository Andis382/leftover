<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { isoWeekday } from '@/lib/dates'
import { formatDate, formatNumber, formatWeekday } from '@/lib/format'
import type { Insights } from '@/types'

/**
 * Sold and left, one bar per day. Days nobody counted are drawn as what they are (skipped,
 * not counted, closed), never as an empty bar that would read as "nothing was left".
 */
const props = withDefaults(defineProps<{ days: Insights['daily']; height?: number }>(), {
  height: 210,
})
const { t } = useI18n()

const scale = computed(() => {
  const max = Math.max(1, ...props.days.map((d) => d.sold + d.left))
  const step = 10 ** Math.floor(Math.log10(max))
  return Math.ceil(max / step) * step
})
const ticks = computed(() => [1, 0.5, 0].map((f) => ({ f, value: Math.round(scale.value * f) })))
const dense = computed(() => props.days.length > 14)

function label(day: string) {
  if (!dense.value)
    return { top: formatWeekday(day, 'short'), bottom: formatDate(day, 'short').split(' ')[0] }
  return isoWeekday(day) === 1
    ? { top: formatDate(day, 'short'), bottom: '' }
    : { top: '', bottom: '' }
}

function title(d: Insights['daily'][number]) {
  const day = `${formatWeekday(d.date, 'short')} ${formatDate(d.date, 'short')}`
  if (d.status !== 'COUNTED') return `${day}: ${t(`insights.status.${d.status}`)}`
  return `${day}: ${t('insights.chartTitle', { sold: d.sold, left: d.left })}${d.soldOuts ? ' · ' + t('insights.soldOutsN', { n: d.soldOuts }, d.soldOuts) : ''}`
}
</script>

<template>
  <figure class="chart" :aria-label="$t('insights.daily')">
    <div class="chart__plot" :style="{ height: `${height}px` }" aria-hidden="true">
      <div
        v-for="tick in ticks"
        :key="tick.f"
        class="chart__tick"
        :style="{ bottom: `${tick.f * 100}%` }"
      >
        <span>{{ formatNumber(tick.value, 0) }}</span>
      </div>
      <div class="chart__cols" :class="{ 'is-dense': dense }">
        <div
          v-for="d in days"
          :key="d.date"
          class="col"
          :class="`col--${d.status.toLowerCase()}`"
          :title="title(d)"
        >
          <div class="col__stack">
            <template v-if="d.status === 'COUNTED'">
              <span v-if="d.soldOuts" class="col__flag num">{{ d.soldOuts }}</span>
              <span v-if="!dense" class="col__left num">{{ formatNumber(d.left, 0) }}</span>
              <span class="seg seg--left" :style="{ height: `${(d.left / scale) * 100}%` }" />
              <span class="seg seg--sold" :style="{ height: `${(d.sold / scale) * 100}%` }" />
            </template>
            <span v-else class="seg seg--gap" />
          </div>
          <span class="col__label">
            <span>{{ label(d.date).top }}</span>
            <span class="subtle">{{ label(d.date).bottom }}</span>
          </span>
        </div>
      </div>
    </div>
    <figcaption class="chart__legend">
      <span class="key"><i class="key__sold" />{{ $t('insights.sold') }}</span>
      <span class="key"><i class="key__left" />{{ $t('insights.left') }}</span>
      <span class="key"><i class="key__flag">3</i>{{ $t('insights.soldOutsKey') }}</span>
      <span class="key"><i class="key__gap" />{{ $t('insights.notCountedKey') }}</span>
    </figcaption>
    <table class="visually-hidden">
      <caption>
        {{
          $t('insights.daily')
        }}
      </caption>
      <thead>
        <tr>
          <th scope="col">{{ $t('common.date') }}</th>
          <th scope="col">{{ $t('insights.sold') }}</th>
          <th scope="col">{{ $t('insights.left') }}</th>
          <th scope="col">{{ $t('insights.soldOutsKey') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="d in days" :key="d.date">
          <th scope="row">{{ formatDate(d.date, 'medium') }}</th>
          <template v-if="d.status === 'COUNTED'">
            <td>{{ d.sold }}</td>
            <td>{{ d.left }}</td>
            <td>{{ d.soldOuts }}</td>
          </template>
          <td v-else colspan="3">{{ $t(`insights.status.${d.status}`) }}</td>
        </tr>
      </tbody>
    </table>
  </figure>
</template>

<style scoped>
.chart {
  margin: 0;
}
.chart__plot {
  position: relative;
  margin: 8px 0 34px 40px;
}
.chart__tick {
  position: absolute;
  left: -40px;
  right: 0;
  border-top: 1px dashed var(--chart-grid);
}
.chart__tick span {
  position: absolute;
  left: 0;
  top: -9px;
  width: 34px;
  text-align: right;
  font-size: 11px;
  color: var(--text-subtle);
  font-variant-numeric: tabular-nums;
}
.chart__cols {
  position: relative;
  display: flex;
  gap: 8px;
  height: 100%;
}
.chart__cols.is-dense {
  gap: 3px;
}
.col {
  position: relative;
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}
.col__stack {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  height: 100%;
}
.seg {
  display: block;
  width: 100%;
  transition: height 400ms var(--ease);
}
.seg--sold {
  background: linear-gradient(
    180deg,
    var(--chart-sold),
    color-mix(in srgb, var(--chart-sold) 80%, var(--brand-300))
  );
  border-radius: 0 0 4px 4px;
}
.seg--left {
  background: linear-gradient(180deg, var(--brand-500), var(--chart-left));
  border-radius: 5px 5px 0 0;
}
.seg--gap {
  height: 38%;
  border-radius: 5px;
  border: 1.5px dashed var(--gray-300);
  background: repeating-linear-gradient(135deg, transparent 0 5px, var(--surface-sunken) 5px 9px);
}
.col--skipped .seg--gap {
  border-color: var(--warning);
  background: repeating-linear-gradient(135deg, transparent 0 5px, var(--warning-soft) 5px 9px);
}
.col--closed .seg--gap,
.col--future .seg--gap {
  height: 6px;
  border: 0;
  background: var(--gray-200);
}
.col--today .seg--gap {
  border-color: var(--primary);
  background: var(--primary-soft);
}
.col__flag {
  align-self: center;
  margin-bottom: 4px;
  min-width: 18px;
  padding: 0 5px;
  border-radius: var(--radius-pill);
  background: var(--accent-500);
  color: var(--header-from);
  font-size: 10px;
  font-weight: 800;
  line-height: 16px;
  text-align: center;
}
.col__left {
  align-self: center;
  margin-bottom: 3px;
  font-size: 11px;
  font-weight: 800;
  color: var(--chart-left);
}
.is-dense .col__flag {
  min-width: 0;
  width: 7px;
  height: 7px;
  padding: 0;
  font-size: 0;
}
.col__label {
  position: absolute;
  top: 100%;
  left: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 6px;
  transform: translateX(-50%);
  font-size: 11px;
  font-weight: 650;
  line-height: 1.2;
  color: var(--text-muted);
  white-space: nowrap;
}
.is-dense .col__label {
  transform: none;
  left: 0;
  align-items: flex-start;
}
.chart__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  font-size: var(--text-xs);
  color: var(--text-muted);
}
.key {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.key i {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 3px;
}
.key__sold {
  background: var(--chart-sold);
}
.key__left {
  background: var(--chart-left);
}
.key .key__flag {
  width: auto;
  height: 14px;
  padding: 0 4px;
  border-radius: var(--radius-pill);
  background: var(--accent-500);
  color: var(--header-from);
  font-size: 9px;
  font-style: normal;
  font-weight: 800;
  line-height: 14px;
}
.key__gap {
  border: 1.5px dashed var(--gray-400);
}
</style>
