<script setup lang="ts">
import { computed } from 'vue'
import { formatDate, formatPercent } from '@/lib/format'
import type { Insights } from '@/types'

/** Waste as a share of what was baked, week by week: the line that should keep going down. */
const props = withDefaults(defineProps<{ weeks: Insights['weekly']; height?: number }>(), {
  height: 170,
})

const max = computed(() => Math.max(0.05, ...props.weeks.map((w) => w.wastePct ?? 0)) * 1.15)
const last = computed(() => props.weeks.length - 1)
</script>

<template>
  <figure class="weeks" :aria-label="$t('insights.weekly')">
    <div class="weeks__plot" :style="{ height: `${height}px` }" aria-hidden="true">
      <div
        v-for="(w, i) in weeks"
        :key="w.weekStart"
        class="week"
        :class="{ 'is-current': i === last }"
      >
        <span class="week__value num">{{
          w.wastePct === null ? '—' : formatPercent(w.wastePct, 1)
        }}</span>
        <span class="week__bar" :style="{ height: `${((w.wastePct ?? 0) / max) * 100}%` }" />
        <span class="week__label">{{
          i === last ? $t('insights.thisWeek') : formatDate(w.weekStart, 'short')
        }}</span>
      </div>
    </div>
    <table class="visually-hidden">
      <caption>
        {{
          $t('insights.weekly')
        }}
      </caption>
      <tbody>
        <tr v-for="w in weeks" :key="w.weekStart">
          <th scope="row">{{ formatDate(w.weekStart, 'medium') }}</th>
          <td>{{ w.wastePct === null ? '—' : formatPercent(w.wastePct, 1) }}</td>
        </tr>
      </tbody>
    </table>
  </figure>
</template>

<style scoped>
.weeks {
  margin: 0;
}
.weeks__plot {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  margin: 22px 0 26px;
  border-bottom: 1px solid var(--border-strong);
}
.week {
  position: relative;
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  align-items: center;
  height: 100%;
}
.week__bar {
  width: 100%;
  max-width: 44px;
  border-radius: 6px 6px 0 0;
  background: linear-gradient(180deg, var(--brand-400), var(--chart-left));
  box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.3);
  transition: height 400ms var(--ease);
}
.week.is-current .week__bar {
  background: repeating-linear-gradient(135deg, var(--brand-300) 0 6px, var(--brand-200) 6px 11px);
}
.week__value {
  margin-bottom: 4px;
  font-size: 11px;
  font-weight: 750;
  color: var(--text-muted);
  white-space: nowrap;
}
.week__label {
  position: absolute;
  top: calc(100% + 6px);
  font-size: 11px;
  font-weight: 600;
  color: var(--text-subtle);
  white-space: nowrap;
}
@media (max-width: 560px) {
  .weeks__plot {
    gap: 4px;
  }
  .week__value {
    font-size: 9px;
  }
  .week__label {
    font-size: 9px;
  }
}
</style>
