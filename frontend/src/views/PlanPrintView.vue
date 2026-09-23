<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { PhArrowLeft, PhPrinter } from '@phosphor-icons/vue'
import BrandMark from '@/components/layout/BrandMark.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import { api } from '@/lib/api'
import { groupByCategory } from '@/lib/count'
import { isDay, weekdayName } from '@/lib/dates'
import { formatDate, formatDateTime, formatTime } from '@/lib/format'
import { formatChange, planTotals } from '@/lib/plan'
import { useAuth } from '@/stores/auth'
import type { Plan } from '@/types'

/** The plan as a sheet for the bakery wall: big numbers, a box to tick, room for notes. */
const route = useRoute()
const { t } = useI18n()
const auth = useAuth()
const plan = ref<Plan | null>(null)
const failed = ref(false)
const date = computed(() => String(route.params.date))
const groups = computed(() => groupByCategory(plan.value?.rows ?? []))
const totals = computed(() => (plan.value ? planTotals(plan.value.rows) : null))
const weekday = computed(() => (plan.value ? weekdayName(plan.value.weekday) : ''))
const printedAt = new Date()

function printSheet() {
  window.print()
}

onMounted(async () => {
  if (!isDay(date.value)) {
    failed.value = true
    return
  }
  try {
    plan.value = await api.get<Plan>(`/plans/${date.value}`)
    document.title = `${t('plan.sheet.title')} · ${formatDate(date.value, 'medium')}`
    await nextTick()
    if (route.query.print !== '0') printSheet()
  } catch {
    failed.value = true
  }
})
</script>

<template>
  <div class="page">
    <div class="toolbar">
      <UiButton variant="ghost" :icon="PhArrowLeft" :to="{ name: 'plan-day', params: { date } }">{{
        $t('plan.title')
      }}</UiButton>
      <UiButton :icon="PhPrinter" :disabled="!plan" @click="printSheet">{{
        $t('plan.print')
      }}</UiButton>
    </div>

    <UiSkeleton v-if="!plan && !failed" card :lines="10" class="paper" />
    <UiEmpty v-else-if="failed" :title="$t('errors.generic')" />

    <article v-else-if="plan && totals" class="paper">
      <header class="sheet-head">
        <div class="sheet-head__shop">
          <BrandMark :size="30" :inverse="false" :with-name="false" />
          <div>
            <p class="shop">{{ auth.organization?.name }}</p>
            <p class="kind">{{ $t('plan.sheet.title') }}</p>
          </div>
        </div>
        <div class="sheet-head__day">
          <p class="day">{{ formatDate(date, 'long') }}</p>
          <p class="meta">
            {{
              plan.generated
                ? $t('plan.madeAt', { time: formatTime(plan.generatedAt) })
                : $t('plan.preview', { time: formatTime(plan.finalAt) })
            }}
          </p>
        </div>
      </header>

      <p class="headline">{{ plan.headline }}</p>

      <table class="grid">
        <thead>
          <tr>
            <th scope="col">{{ $t('plan.cols.product') }}</th>
            <th scope="col" class="n">{{ $t('plan.sheet.tray') }}</th>
            <th scope="col" class="n">{{ $t('plan.cols.last', { weekday }) }}</th>
            <th scope="col" class="n bake">{{ $t('plan.sheet.bake') }}</th>
            <th scope="col" class="n">{{ $t('plan.sheet.trays') }}</th>
            <th scope="col" class="n">{{ $t('plan.cols.change') }}</th>
            <th scope="col" class="tick">{{ $t('plan.sheet.done') }}</th>
            <th scope="col" class="notes">{{ $t('plan.sheet.notes') }}</th>
          </tr>
        </thead>
        <tbody v-for="g in groups" :key="g.category">
          <tr class="cat">
            <th colspan="8" scope="colgroup">{{ $t(`categories.${g.category}`) }}</th>
          </tr>
          <tr v-for="row in g.items" :key="row.productId">
            <th scope="row">{{ row.name }}</th>
            <td class="n muted">{{ row.traySize }}</td>
            <td class="n muted">{{ row.lastBaked ?? '—' }}</td>
            <td class="n bake">{{ row.willBake }}</td>
            <td class="n">{{ Math.ceil(row.willBake / Math.max(1, row.traySize)) }}</td>
            <td class="n change">{{ formatChange(row.change) }}</td>
            <td class="tick"><span class="box" aria-hidden="true" /></td>
            <td class="notes" />
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <th scope="row">{{ $t('plan.sheet.total') }}</th>
            <td />
            <td />
            <td class="n bake">{{ totals.units }}</td>
            <td class="n">{{ totals.trays }}</td>
            <td colspan="3" />
          </tr>
        </tfoot>
      </table>

      <footer class="sheet-foot">
        <span>{{ $t('plan.sheet.baker') }} ____________________</span>
        <span>{{ $t('plan.sheet.started') }} ________</span>
        <span>{{ $t('plan.sheet.finished') }} ________</span>
        <span class="printed">{{
          $t('plan.sheet.printed', { time: formatDateTime(printedAt) })
        }}</span>
      </footer>
    </article>
  </div>
</template>

<style scoped>
.page {
  min-height: 100dvh;
  padding: 24px var(--gutter) 48px;
  background:
    radial-gradient(
      900px 400px at 50% -10%,
      color-mix(in srgb, var(--accent-300) 25%, transparent),
      transparent 70%
    ),
    var(--surface-sunken);
}
.toolbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  max-width: 210mm;
  margin: 0 auto 16px;
}
.paper {
  max-width: 210mm;
  margin: 0 auto;
  padding: 14mm 12mm;
  background: var(--paper);
  color: var(--text);
  border-radius: 4px;
  box-shadow: var(--shadow-xl);
}
.sheet-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
  padding-bottom: 10px;
  border-bottom: 2.5px solid var(--text);
}
.sheet-head__shop {
  display: flex;
  align-items: center;
  gap: 10px;
}
.shop {
  font-family: var(--font-display);
  font-size: 1.35rem;
  font-weight: 700;
  line-height: 1.1;
}
.kind {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--text-muted);
}
.sheet-head__day {
  text-align: right;
}
.day {
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 650;
  text-transform: capitalize;
}
.meta {
  font-size: 0.78rem;
  color: var(--text-muted);
}
.headline {
  margin: 12px 0 14px;
  padding: 10px 12px;
  border-left: 4px solid var(--accent-500);
  background: color-mix(in srgb, var(--accent-soft) 70%, transparent);
  font-family: var(--font-display);
  font-size: 1.05rem;
  font-weight: 560;
  line-height: 1.35;
}
.grid {
  font-size: 0.86rem;
  font-variant-numeric: tabular-nums;
}
.grid th,
.grid td {
  padding: 5px 6px;
  border-bottom: 1px solid var(--gray-300);
  text-align: left;
}
.grid thead th {
  font-size: 0.66rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1.5px solid var(--text);
}
.grid .n {
  text-align: right;
}
.grid .bake {
  font-family: var(--font-display);
  font-size: 1.05rem;
  font-weight: 700;
}
.grid thead .bake {
  font-family: var(--font-body);
  font-size: 0.66rem;
}
.grid .change {
  color: var(--text-muted);
  font-weight: 650;
}
.grid .cat th {
  padding-top: 10px;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--primary-strong);
  border-bottom: 1px solid var(--gray-400);
}
.grid .tick {
  width: 44px;
  text-align: center;
}
.box {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 1.5px solid var(--text);
  border-radius: 3px;
  vertical-align: middle;
}
.grid .notes {
  width: 26%;
  border-bottom-style: dotted;
}
.grid tfoot th,
.grid tfoot td {
  border-top: 2px solid var(--text);
  border-bottom: 0;
  font-weight: 800;
}
.sheet-foot {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 24px;
  margin-top: 18px;
  font-size: 0.8rem;
  color: var(--text-muted);
}
.printed {
  margin-left: auto;
}

@media print {
  @page {
    size: A4 portrait;
    margin: 10mm;
  }
  .page {
    padding: 0;
    background: none;
  }
  .toolbar {
    display: none;
  }
  .paper {
    max-width: none;
    padding: 0;
    box-shadow: none;
    background: none;
  }
  .grid tr {
    break-inside: avoid;
  }
}
@media (max-width: 640px) {
  .paper {
    padding: 18px 12px;
  }
  .grid .notes,
  .grid thead .notes {
    display: none;
  }
  .sheet-head {
    flex-direction: column;
    align-items: flex-start;
  }
  .sheet-head__day {
    text-align: left;
  }
}
</style>
