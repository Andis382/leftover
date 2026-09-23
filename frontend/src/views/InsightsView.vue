<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  PhChartBar,
  PhClockCountdown,
  PhCoins,
  PhPercent,
  PhTrash,
  PhTrendDown,
  PhTrendUp,
} from '@phosphor-icons/vue'
import AppPage from '@/components/layout/AppPage.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import UiSegmented from '@/components/ui/UiSegmented.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiButton from '@/components/ui/UiButton.vue'
import DailyChart from '@/components/insights/DailyChart.vue'
import WeeklyChart from '@/components/insights/WeeklyChart.vue'
import DayStrip from '@/components/insights/DayStrip.vue'
import { api } from '@/lib/api'
import { formatDate, formatMoney, formatNumber, formatPercent, formatWeekday } from '@/lib/format'
import type { Insights } from '@/types'

const { t, locale } = useI18n()
// A month by default: with 20–40 products a single week is too noisy to judge.
const days = ref<7 | 30>(30)
const data = ref<Insights | null>(null)
const loading = ref(true)
const failed = ref(false)

const periods = computed(() => [
  { value: 7 as const, label: t('insights.period7') },
  { value: 30 as const, label: t('insights.period30') },
])
const empty = computed(
  () =>
    !!data.value && data.value.current.countedDays === 0 && data.value.previous.countedDays === 0,
)

async function load() {
  loading.value = true
  failed.value = false
  try {
    data.value = await api.get<Insights>(`/insights?days=${days.value}`)
  } catch {
    failed.value = true
  } finally {
    loading.value = false
  }
}

/** "−24% vs the previous 7 days", coloured by whether that is good news (less waste is). */
function compare(now: number | null, before: number | null, lowerIsBetter = true) {
  if (now === null || before === null || before === 0)
    return { hint: t('insights.noPrevious'), trend: null }
  const change = (now - before) / before
  if (Math.abs(change) < 0.005)
    return { hint: t('insights.sameAsPrevious', { days: days.value }), trend: null }
  const good = lowerIsBetter ? change < 0 : change > 0
  return {
    hint: t('insights.vsPrevious', {
      change: (change > 0 ? '+' : '−') + formatPercent(Math.abs(change)),
      days: days.value,
    }),
    trend: Math.abs(change) < 0.02 ? null : good ? ('up' as const) : ('down' as const),
  }
}

const stats = computed(() => {
  if (!data.value) return null
  const { current: c, previous: p } = data.value
  return {
    left: compare(c.leftUnits, p.countedDays ? p.leftUnits : null),
    value: compare(c.wasteCents, p.countedDays ? p.wasteCents : null),
    pct: compare(c.wastePct, p.wastePct),
  }
})

watch([days, locale], load, { immediate: true })
</script>

<template>
  <AppPage :title="$t('insights.title')" :subtitle="$t('insights.subtitle')">
    <template #actions>
      <UiSegmented v-model="days" :options="periods" :label="$t('insights.period')" />
    </template>

    <UiSkeleton v-if="loading && !data" card :lines="8" />

    <UiEmpty v-else-if="failed" :icon="PhChartBar" :title="$t('errors.generic')">
      <UiButton variant="secondary" @click="load">{{ $t('common.retry') }}</UiButton>
    </UiEmpty>

    <UiEmpty
      v-else-if="empty"
      :icon="PhChartBar"
      :title="$t('insights.emptyTitle')"
      :text="$t('insights.emptyText')"
    >
      <UiButton :to="{ name: 'count' }">{{ $t('nav.count') }}</UiButton>
    </UiEmpty>

    <template v-else-if="data && stats">
      <div class="stats">
        <UiStat
          :label="$t('insights.leftOver')"
          :value="formatNumber(data.current.leftUnits, 0)"
          :icon="PhTrash"
          :hint="stats.left.hint"
          :trend="stats.left.trend"
        />
        <UiStat
          :label="$t('insights.wasted')"
          :value="formatMoney(data.current.wasteCents)"
          :icon="PhCoins"
          tone="danger"
          :hint="stats.value.hint"
          :trend="stats.value.trend"
        />
        <UiStat
          :label="$t('insights.ofProduction')"
          :value="data.current.wastePct === null ? '—' : formatPercent(data.current.wastePct, 1)"
          :icon="PhPercent"
          tone="warning"
          :hint="stats.pct.hint"
          :trend="stats.pct.trend"
        />
        <UiStat
          :label="$t('insights.soldOuts')"
          :value="data.current.soldOuts"
          :icon="PhClockCountdown"
          tone="info"
          :hint="$t('insights.missed', { amount: formatMoney(data.current.missedCents) })"
        />
      </div>

      <UiCard
        :title="$t('insights.daily')"
        :subtitle="
          $t('insights.dailyHint', {
            from: formatDate(data.from, 'short'),
            to: formatDate(data.to, 'short'),
          })
        "
      >
        <DailyChart :days="data.daily" />
      </UiCard>

      <div class="two">
        <UiCard :title="$t('insights.weekly')" :subtitle="$t('insights.weeklyHint')">
          <WeeklyChart :weeks="data.weekly" />
        </UiCard>
        <UiCard :title="$t('insights.calendar')" :subtitle="$t('insights.calendarHint')">
          <DayStrip :days="data.calendar" />
        </UiCard>
      </div>

      <UiCard :title="$t('insights.sellouts')" :subtitle="$t('insights.selloutsHint')">
        <div class="log">
          <ul v-if="data.patterns.length" class="patterns">
            <li v-for="p in data.patterns" :key="`${p.productId}-${p.weekday}`">
              <span class="patterns__icon"
                ><PhClockCountdown :size="16" weight="bold" aria-hidden="true"
              /></span>
              <span>{{ p.text }}</span>
            </li>
          </ul>
          <p v-else class="muted small">{{ $t('insights.noPatterns') }}</p>
          <div>
            <p class="eyebrow">{{ $t('insights.recent') }}</p>
            <ul v-if="data.sellouts.length" class="events">
              <li v-for="s in data.sellouts" :key="`${s.date}-${s.productId}`">
                <span class="events__when num"
                  >{{ formatWeekday(s.date, 'short') }} {{ formatDate(s.date, 'short') }}</span
                >
                <span class="events__name">{{ s.name }}</span>
                <span class="events__time num">{{ s.time }}</span>
              </li>
            </ul>
            <p v-else class="muted small">{{ $t('insights.noSellouts') }}</p>
          </div>
        </div>
      </UiCard>

      <UiCard
        :title="$t('insights.byProduct')"
        :subtitle="$t('insights.byProductHint')"
        padding="none"
      >
        <div class="table-wrap">
          <table class="table ptable">
            <thead>
              <tr>
                <th scope="col">{{ $t('insights.cols.product') }}</th>
                <th scope="col" class="r">{{ $t('insights.cols.avgLeft') }}</th>
                <th scope="col" class="r">{{ $t('insights.cols.wastePct') }}</th>
                <th scope="col" class="r">{{ $t('insights.cols.soldOut') }}</th>
                <th scope="col" class="r">{{ $t('insights.cols.wasted') }}</th>
                <th scope="col">{{ $t('insights.cols.trend') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in data.products" :key="p.productId">
                <th scope="row" class="strong c-name">{{ p.name }}</th>
                <td class="r num c-avg" :data-label="$t('insights.cols.avgLeft')">
                  {{ formatNumber(p.avgLeft, 1) }}
                </td>
                <td class="r num c-pct" :data-label="$t('insights.cols.wastePct')">
                  {{ p.wastePct === null ? '—' : formatPercent(p.wastePct, 0) }}
                </td>
                <td class="r num c-sold" :data-label="$t('insights.cols.soldOut')">
                  {{ $t('insights.soldOutDays', { n: p.soldOutDays, of: p.countedDays }) }}
                </td>
                <td class="r num strong c-wasted">
                  {{ formatMoney(p.wasteCents, { decimals: true }) }}
                </td>
                <td class="c-trend">
                  <UiBadge v-if="p.trend === 'down'" tone="success" :icon="PhTrendDown" size="sm">{{
                    $t('insights.trend.down', { was: formatNumber(p.previousAvgLeft, 1) })
                  }}</UiBadge>
                  <UiBadge v-else-if="p.trend === 'up'" tone="danger" :icon="PhTrendUp" size="sm">{{
                    $t('insights.trend.up', { was: formatNumber(p.previousAvgLeft, 1) })
                  }}</UiBadge>
                  <UiBadge v-else size="sm">{{ $t(`insights.trend.${p.trend}`) }}</UiBadge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>
    </template>
  </AppPage>
</template>

<style scoped>
.stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}
.two {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
  gap: 16px;
}
.log {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
  gap: 20px 28px;
}
.patterns,
.events {
  margin: 0;
  padding: 0;
  list-style: none;
}
.patterns {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.patterns li {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 10px 12px;
  border-radius: var(--radius);
  background: color-mix(in srgb, var(--accent-soft) 70%, transparent);
  border: 1px solid color-mix(in srgb, var(--accent-500) 30%, transparent);
  font-size: var(--text-sm);
  font-weight: 550;
}
.patterns__icon {
  display: grid;
  place-items: center;
  flex: none;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  color: var(--accent-soft-text);
  background: var(--surface);
}
.events {
  margin-top: 8px;
}
.events li {
  display: grid;
  grid-template-columns: 96px minmax(0, 1fr) auto;
  gap: 10px;
  padding: 7px 0;
  border-bottom: 1px dashed var(--border-strong);
  font-size: var(--text-sm);
}
.events li:last-child {
  border-bottom: 0;
}
.events__when {
  color: var(--text-subtle);
}
.events__time {
  font-weight: 700;
  color: var(--accent-soft-text);
}
.table .r {
  text-align: right;
}
.table th[scope='row'] {
  text-align: left;
  font-size: var(--text-sm);
  text-transform: none;
  letter-spacing: 0;
  color: var(--text);
  background: none;
}
@media (max-width: 1040px) {
  .stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .two,
  .log {
    grid-template-columns: minmax(0, 1fr);
  }
}
@media (max-width: 640px) {
  .ptable thead {
    display: none;
  }
  .ptable,
  .ptable tbody {
    display: block;
  }
  .ptable tbody tr {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-template-areas:
      'name name wasted'
      'avg pct sold'
      'trend trend trend';
    gap: 8px 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
  }
  .ptable tbody th,
  .ptable tbody td {
    display: block;
    padding: 0;
    border: 0;
    text-align: left;
  }
  .c-name {
    grid-area: name;
  }
  .ptable tbody td.r {
    text-align: left;
  }
  .ptable tbody td.c-wasted {
    grid-area: wasted;
    text-align: right;
  }
  .c-avg {
    grid-area: avg;
  }
  .c-pct {
    grid-area: pct;
  }
  .c-sold {
    grid-area: sold;
  }
  .c-trend {
    grid-area: trend;
  }
  .c-avg::before,
  .c-pct::before,
  .c-sold::before {
    content: attr(data-label);
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text-subtle);
  }
}
</style>
