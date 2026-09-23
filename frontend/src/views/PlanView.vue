<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  PhArrowsDownUp,
  PhCalendarX,
  PhCoins,
  PhOven,
  PhPrinter,
  PhStack,
  PhWhatsappLogo,
} from '@phosphor-icons/vue'
import AppPage from '@/components/layout/AppPage.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import UiNotice from '@/components/ui/UiNotice.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiStepper from '@/components/ui/UiStepper.vue'
import DatePager from '@/components/plan/DatePager.vue'
import ChangeChip from '@/components/plan/ChangeChip.vue'
import ConfidenceBars from '@/components/plan/ConfidenceBars.vue'
import SendPlanDialog from '@/components/plan/SendPlanDialog.vue'
import { api, ApiError } from '@/lib/api'
import { CATEGORY_ICONS } from '@/lib/categories'
import { groupByCategory } from '@/lib/count'
import { isDay, shopNow, weekdayName } from '@/lib/dates'
import { formatDate, formatMoney, formatPhone, formatTime } from '@/lib/format'
import { planTotals } from '@/lib/plan'
import { useAuth } from '@/stores/auth'
import { useToasts } from '@/stores/toasts'
import type { Plan, PlanRow } from '@/types'

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const auth = useAuth()
const toasts = useToasts()

const today = computed(() => shopNow(auth.organization?.timezone).day)
const date = computed(() => String(route.params.date))
const plan = ref<Plan | null>(null)
const loading = ref(true)
const failed = ref(false)
const sendOpen = ref(false)

const isOwner = computed(() => auth.hasRole('OWNER'))
// Once the morning's baking is confirmed the numbers are what was baked, changed only on "This morning".
const baked = computed(() => !!plan.value?.bakedConfirmedAt)
const editable = computed(
  () => isOwner.value && !!plan.value && !plan.value.isPast && plan.value.shopOpen && !baked.value,
)
const groups = computed(() => groupByCategory(plan.value?.rows ?? []))
const totals = computed(() =>
  plan.value ? { ...plan.value.totals, ...planTotals(plan.value.rows) } : null,
)
const weekday = computed(() => (plan.value ? weekdayName(plan.value.weekday) : ''))
const printHref = computed(
  () => router.resolve({ name: 'plan-print', params: { date: date.value } }).href,
)

async function load() {
  if (!isDay(date.value)) {
    router.replace({ name: 'plan' })
    return
  }
  loading.value = true
  failed.value = false
  try {
    plan.value = await api.get<Plan>(`/plans/${date.value}`)
  } catch {
    failed.value = true
    plan.value = null
  } finally {
    loading.value = false
  }
}

// Changes to "will bake" show at once and are saved a moment after the last tap.
const pending = new Map<number, ReturnType<typeof setTimeout>>()

function setWillBake(row: PlanRow, value: number | null) {
  if (!plan.value) return
  const willBake = value ?? row.suggested
  plan.value.rows = plan.value.rows.map((r) =>
    r.productId === row.productId
      ? {
          ...r,
          willBake,
          overridden: willBake !== r.suggested,
          change: r.lastBaked === null ? null : willBake - r.lastBaked,
        }
      : r,
  )
  clearTimeout(pending.get(row.productId))
  pending.set(
    row.productId,
    setTimeout(() => save(row.productId, willBake), 450),
  )
}

async function save(productId: number, willBake: number) {
  const day = date.value
  try {
    const updated = await api.put<Plan>(`/plans/${day}/items/${productId}`, { willBake })
    pending.delete(productId)
    if (day !== date.value || !plan.value) return
    const local = new Map(plan.value.rows.map((r) => [r.productId, r]))
    plan.value = {
      ...updated,
      rows: updated.rows.map((r) => (pending.has(r.productId) ? (local.get(r.productId) ?? r) : r)),
    }
  } catch (e) {
    pending.delete(productId)
    toasts.error(
      e instanceof ApiError && e.status === 0
        ? t('errors.network')
        : (e as Error).message || t('errors.generic'),
    )
    load()
  }
}

onBeforeUnmount(() => pending.forEach((timer) => clearTimeout(timer)))

watch([date, locale], load, { immediate: true })
</script>

<template>
  <AppPage :title="$t('plan.title')" :subtitle="formatDate(date, 'long')">
    <template #meta>
      <DatePager :date="date" :today="today" route="plan-day" />
    </template>
    <template #actions>
      <UiButton variant="inverse" :icon="PhPrinter" :href="printHref" target="_blank">{{
        $t('plan.print')
      }}</UiButton>
      <UiButton
        v-if="isOwner && plan?.shopOpen && plan.rows.length"
        :icon="PhWhatsappLogo"
        @click="sendOpen = true"
        >{{ $t('plan.send') }}</UiButton
      >
    </template>

    <UiSkeleton v-if="loading && !plan" card :lines="8" />

    <UiEmpty v-else-if="failed" :icon="PhCalendarX" :title="$t('errors.generic')">
      <UiButton variant="secondary" @click="load">{{ $t('common.retry') }}</UiButton>
    </UiEmpty>

    <UiEmpty
      v-else-if="plan && !plan.shopOpen"
      :icon="PhCalendarX"
      :title="$t('plan.closedTitle', { weekday })"
      :text="$t('plan.closedText')"
    />

    <UiEmpty
      v-else-if="plan && !plan.rows.length"
      :icon="PhOven"
      :title="$t('plan.emptyTitle')"
      :text="$t('plan.emptyText', { weekday })"
    >
      <UiButton v-if="isOwner" variant="secondary" :to="{ name: 'products' }">{{
        $t('nav.products')
      }}</UiButton>
    </UiEmpty>

    <template v-else-if="plan && totals">
      <section class="headline" :aria-label="$t('plan.headlineLabel')">
        <div class="headline__status">
          <UiBadge v-if="plan.generated" tone="success" dot>{{
            plan.generatedBy
              ? $t('plan.changedBy', { name: plan.generatedBy })
              : $t('plan.madeAt', { time: formatTime(plan.generatedAt) })
          }}</UiBadge>
          <UiBadge v-else tone="info" dot>{{
            $t('plan.preview', { time: formatTime(plan.finalAt) })
          }}</UiBadge>
          <UiBadge v-if="plan.sentAt" tone="accent" :icon="PhWhatsappLogo">{{
            $t('plan.sentAt', { time: formatTime(plan.sentAt), phone: formatPhone(plan.sentTo) })
          }}</UiBadge>
          <UiBadge v-if="plan.bakedConfirmedAt" tone="neutral">{{
            $t('plan.bakedConfirmed', { time: formatTime(plan.bakedConfirmedAt) })
          }}</UiBadge>
        </div>
        <p class="headline__text">{{ plan.headline }}</p>
        <p class="headline__hint">{{ $t('plan.headlineHint', { weekday }) }}</p>
      </section>

      <div class="stats">
        <UiStat
          :label="$t('plan.stats.toBake')"
          :value="totals.units"
          :icon="PhOven"
          :hint="$t('plan.stats.products', { n: plan.rows.length })"
        />
        <UiStat
          :label="$t('plan.stats.trays')"
          :value="totals.trays"
          :icon="PhStack"
          tone="neutral"
        />
        <UiStat
          :label="$t('plan.stats.worth')"
          :value="formatMoney(totals.valueCents, { decimals: false })"
          :icon="PhCoins"
          tone="warning"
        />
        <UiStat
          :label="$t('plan.stats.changes')"
          :value="totals.more + totals.fewer"
          :icon="PhArrowsDownUp"
          tone="info"
          :hint="$t('plan.stats.changesHint', { more: totals.more, fewer: totals.fewer, weekday })"
        />
      </div>

      <UiNotice v-if="plan.isPast" tone="info">{{ $t('plan.pastLocked') }}</UiNotice>
      <UiNotice v-else-if="baked && isOwner" tone="info">
        {{ $t('plan.bakedLocked') }}
        <template #actions>
          <UiButton size="sm" variant="secondary" :to="{ name: 'morning' }">{{
            $t('morning.title')
          }}</UiButton>
        </template>
      </UiNotice>
      <UiNotice v-else-if="!isOwner" tone="info">{{ $t('plan.staffReadOnly') }}</UiNotice>

      <div class="sheet">
        <table class="ptable">
          <caption class="visually-hidden">
            {{
              $t('plan.title')
            }}
            ·
            {{
              formatDate(date, 'long')
            }}
          </caption>
          <thead>
            <tr>
              <th scope="col">{{ $t('plan.cols.product') }}</th>
              <th scope="col" class="num">{{ $t('plan.cols.last', { weekday }) }}</th>
              <th scope="col" class="num">{{ $t('plan.cols.suggested') }}</th>
              <th scope="col">{{ $t('plan.cols.change') }}</th>
              <th scope="col">{{ baked ? $t('plan.cols.baked') : $t('plan.cols.willBake') }}</th>
              <th scope="col">{{ $t('plan.cols.why') }}</th>
            </tr>
          </thead>
          <tbody v-for="g in groups" :key="g.category">
            <tr class="ptable__cat">
              <th colspan="6" scope="colgroup">
                <span class="cat">
                  <component
                    :is="CATEGORY_ICONS[g.category]"
                    :size="16"
                    weight="duotone"
                    aria-hidden="true"
                  />
                  {{ $t(`categories.${g.category}`) }}
                </span>
              </th>
            </tr>
            <tr
              v-for="row in g.items"
              :key="row.productId"
              class="ptable__row"
              :class="{ 'is-overridden': row.overridden }"
            >
              <th scope="row" class="c-name">
                {{ row.name }}
                <span v-if="row.traySize > 1" class="c-name__tray">{{
                  $t('plan.tray', { n: row.traySize })
                }}</span>
              </th>
              <td class="c-last num" :data-label="$t('plan.cols.last', { weekday })">
                {{ row.lastBaked ?? '—' }}
              </td>
              <td class="c-sugg num" :data-label="$t('plan.cols.suggested')">
                {{ row.suggested }}
              </td>
              <td class="c-change"><ChangeChip :change="row.change" /></td>
              <td class="c-bake">
                <template v-if="editable">
                  <UiStepper
                    :model-value="row.willBake"
                    :step="row.traySize"
                    :label="$t('plan.willBakeLabel', { name: row.name })"
                    @update:model-value="(v) => setWillBake(row, v)"
                  />
                  <button
                    v-if="row.overridden"
                    type="button"
                    class="linkish"
                    @click="setWillBake(row, row.suggested)"
                  >
                    {{ $t('plan.useSuggestion', { n: row.suggested }) }}
                  </button>
                </template>
                <span v-else class="bake-number num">{{ row.willBake }}</span>
              </td>
              <td class="c-why">
                <span class="c-why__text">{{ row.reason }}</span>
                <ConfidenceBars :confidence="row.confidence" :observations="row.observations" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="plan.notBaked.length" class="small muted">
        {{ $t('plan.notBaked', { weekday, names: plan.notBaked.map((p) => p.name).join(', ') }) }}
      </p>
    </template>

    <SendPlanDialog
      v-if="plan"
      v-model:open="sendOpen"
      :date="date"
      :time="formatTime(plan.finalAt)"
      @sent="(p) => (plan = p)"
    />
  </AppPage>
</template>

<style scoped>
.headline {
  position: relative;
  padding: clamp(20px, 3vw, 30px) clamp(20px, 3vw, 32px);
  background:
    radial-gradient(
      700px 240px at 100% 0%,
      color-mix(in srgb, var(--accent-300) 35%, transparent),
      transparent 70%
    ),
    var(--paper);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg), var(--highlight);
  overflow: hidden;
}
.headline::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 5px;
  background: linear-gradient(180deg, var(--accent-400), var(--brand-500));
}
.headline__status {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.headline__text {
  margin-top: 14px;
  font-family: var(--font-display);
  font-size: clamp(1.35rem, 1rem + 1.6vw, 2.1rem);
  font-weight: 560;
  font-variation-settings:
    'SOFT' 80,
    'WONK' 0;
  line-height: 1.25;
  letter-spacing: -0.015em;
  color: var(--text);
  max-width: 44ch;
  text-wrap: balance;
}
.headline__hint {
  margin-top: 10px;
  font-size: var(--text-sm);
  color: var(--text-muted);
  max-width: 70ch;
}
.stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}
.sheet {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md), var(--highlight);
  overflow: hidden;
}
.ptable {
  font-size: var(--text-sm);
}
.ptable thead th {
  padding: 12px 14px;
  text-align: left;
  font-size: var(--text-xs);
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--text-subtle);
  background: var(--surface-muted);
  border-bottom: 1px solid var(--border);
}
.ptable thead th.num {
  text-align: right;
}
.ptable__cat th {
  padding: 14px 14px 8px;
  text-align: left;
  background: linear-gradient(180deg, var(--surface-muted), var(--surface));
}
.cat {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-display);
  font-size: var(--text-md);
  font-weight: 640;
  color: var(--primary-strong);
}
.ptable__row th,
.ptable__row td {
  padding: 12px 14px;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.ptable__row:hover {
  background: var(--surface-hover);
}
.ptable__row.is-overridden {
  background: color-mix(in srgb, var(--accent-soft) 55%, transparent);
}
.c-name {
  text-align: left;
  font-weight: 700;
  font-size: var(--text-md);
  width: 24%;
}
.c-name__tray {
  display: block;
  font-size: var(--text-xs);
  font-weight: 550;
  color: var(--text-subtle);
}
.c-last,
.c-sugg {
  text-align: right;
  width: 9%;
}
.c-last {
  color: var(--text-muted);
}
.c-sugg {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 650;
  color: var(--text);
}
.c-change {
  width: 8%;
}
.c-bake {
  width: 19%;
}
.c-bake :deep(.stepper__input) {
  font-size: var(--text-lg);
}
.bake-number {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 650;
}
.linkish {
  display: block;
  margin-top: 6px;
  padding: 0;
  border: 0;
  background: none;
  color: var(--primary);
  font-size: var(--text-xs);
  font-weight: 650;
  text-decoration: underline;
  text-underline-offset: 3px;
}
.c-why {
  color: var(--text-muted);
}
.c-why__text {
  margin-right: 8px;
}
@media (max-width: 1040px) {
  .stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 760px) {
  .ptable thead {
    display: none;
  }
  .ptable,
  .ptable tbody {
    display: block;
  }
  .ptable__cat,
  .ptable__cat th {
    display: block;
  }
  .ptable__row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    grid-template-areas:
      'name change'
      'why why'
      'last bake'
      'sugg bake';
    gap: 6px 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
  }
  .ptable__row th,
  .ptable__row td {
    display: block;
    width: auto;
    padding: 0;
    border: 0;
    text-align: left;
  }
  .c-name {
    grid-area: name;
  }
  .c-change {
    grid-area: change;
    justify-self: end;
  }
  .c-why {
    grid-area: why;
    font-size: var(--text-sm);
  }
  .c-last {
    grid-area: last;
    align-self: end;
  }
  .c-sugg {
    grid-area: sugg;
    font-size: var(--text-lg);
  }
  .c-last::before,
  .c-sugg::before {
    content: attr(data-label) ' ';
    font-family: var(--font-body);
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--text-subtle);
  }
  .c-bake {
    grid-area: bake;
    align-self: center;
    justify-self: end;
    text-align: right;
  }
}
</style>
