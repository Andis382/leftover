<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  PhArrowCounterClockwise,
  PhCalendarX,
  PhCheckFat,
  PhSkipForward,
  PhStorefront,
} from '@phosphor-icons/vue'
import AppPage from '@/components/layout/AppPage.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import UiNotice from '@/components/ui/UiNotice.vue'
import UiProgress from '@/components/ui/UiProgress.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import CountRow from '@/components/count/CountRow.vue'
import CountDone from '@/components/count/CountDone.vue'
import SoldOutDialog from '@/components/count/SoldOutDialog.vue'
import SkipDialog from '@/components/count/SkipDialog.vue'
import SyncBadge from '@/components/count/SyncBadge.vue'
import { api, ApiError } from '@/lib/api'
import { countKey, groupByCategory, pendingCount, summarize, withPending } from '@/lib/count'
import { CATEGORY_ICONS } from '@/lib/categories'
import { isDay, shopNow } from '@/lib/dates'
import { formatDate, formatMoney } from '@/lib/format'
import { useAuth } from '@/stores/auth'
import { useConfirm } from '@/stores/confirm'
import { useCountSync } from '@/stores/countSync'
import { useToasts } from '@/stores/toasts'
import type { CountItem, CountSheet } from '@/types'

const route = useRoute()
const { t } = useI18n()
const auth = useAuth()
const sync = useCountSync()
const toasts = useToasts()
const confirm = useConfirm()

const today = computed(() => shopNow(auth.organization?.timezone).day)
const date = computed(() => {
  const asked = route.query.date
  return isDay(asked) && asked <= today.value ? asked : today.value
})
const isToday = computed(() => date.value === today.value)

const sheet = ref<CountSheet | null>(null)
const items = ref<CountItem[]>([])
const loading = ref(true)
const failed = ref(false)
const fromCache = ref(false)
const editing = ref(false)
const finishing = ref(false)
const skipOpen = ref(false)
const skipping = ref(false)
const soldOutOpen = ref(false)
const soldOutItem = ref<CountItem | null>(null)

const summary = computed(() => summarize(items.value))
const groups = computed(() => groupByCategory(items.value))
const status = computed(() => sheet.value?.status ?? 'OPEN')
const showList = computed(
  () => !!sheet.value && items.value.length > 0 && (status.value === 'OPEN' || editing.value),
)
const waitingHere = computed(() => pendingCount(sync.queue, date.value))
const previousMissing = computed(() => {
  const previous = sheet.value?.previous
  return isToday.value && previous && previous.status === 'OPEN' ? previous : null
})

function cacheKey() {
  return `leftover:count-sheet:${auth.user?.id ?? 'guest'}:${date.value}`
}

/** The last list this phone saw, so a count can go on without a connection. */
function remember(data: CountSheet) {
  try {
    localStorage.setItem(cacheKey(), JSON.stringify(data))
  } catch {
    /* storage full or blocked: the live list still works */
  }
}

function recall(): CountSheet | null {
  try {
    const raw = localStorage.getItem(cacheKey())
    return raw ? (JSON.parse(raw) as CountSheet) : null
  } catch {
    return null
  }
}

async function load() {
  loading.value = true
  failed.value = false
  editing.value = false
  sync.load()
  try {
    sheet.value = await api.get<CountSheet>(`/count/${date.value}`)
    fromCache.value = false
    remember(sheet.value)
  } catch (e) {
    sheet.value = e instanceof ApiError && e.status === 0 ? recall() : null
    fromCache.value = sheet.value !== null
    failed.value = sheet.value === null
  } finally {
    loading.value = false
  }
  items.value = sheet.value ? withPending(sheet.value.items, sync.queue, date.value) : []
  sync.flush()
}

function change(item: CountItem, left: number | null, soldOutAt: string | null) {
  const next = { ...item, left, soldOutAt: left === 0 ? soldOutAt : null }
  items.value = items.value.map((i) => (i.productId === item.productId ? next : i))
  sync.record(date.value, item.productId, next.left, next.soldOutAt)
}

function openSoldOut(item: CountItem) {
  soldOutItem.value = item
  soldOutOpen.value = true
}

function pickSoldOut(time: string | null) {
  const item = items.value.find((i) => i.productId === soldOutItem.value?.productId)
  if (!item) return
  if (time) change(item, 0, time)
  else change(item, item.left, null)
}

/** Enter on the phone keyboard moves to the next product, like filling in a paper list. */
async function focusNext(item: CountItem) {
  const order = groups.value.flatMap((g) => g.items)
  const next = order[order.findIndex((i) => i.productId === item.productId) + 1]
  await nextTick()
  if (next) document.getElementById(`left-${next.productId}`)?.focus()
  else (document.activeElement as HTMLElement | null)?.blur()
}

const stopListening = sync.listen({
  saved(savedDate, item) {
    if (savedDate !== date.value || sync.queue[countKey(savedDate, item.productId)]) return
    items.value = items.value.map((i) => (i.productId === item.productId ? item : i))
  },
  rejected(change, message) {
    if (change.date !== date.value) return
    const name = items.value.find((i) => i.productId === change.productId)?.name ?? ''
    toasts.error(t('count.sync.error', { name }), message)
    load()
  },
})
onBeforeUnmount(stopListening)

async function finish() {
  if (summary.value.counted === 0) {
    toasts.error(t('count.nothingCounted'))
    return
  }
  const uncounted = summary.value.total - summary.value.counted
  if (
    uncounted > 0 &&
    !(await confirm.ask({
      title: t('count.uncountedTitle', { n: uncounted }),
      text: t('count.uncountedText'),
      confirmLabel: t('count.finishAnyway'),
      cancelLabel: t('count.keepCounting'),
    }))
  ) {
    return
  }
  finishing.value = true
  try {
    await sync.flush()
    if (pendingCount(sync.queue, date.value) > 0) {
      toasts.error(t('count.finishOffline'))
      return
    }
    sheet.value = await api.post<CountSheet>(`/count/${date.value}/finish`)
    items.value = sheet.value.items
    remember(sheet.value)
    editing.value = false
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e) {
    toasts.error(
      e instanceof ApiError && e.status === 0 ? t('errors.network') : t('errors.generic'),
    )
  } finally {
    finishing.value = false
  }
}

async function skip(reason: string, day = date.value) {
  skipping.value = true
  try {
    await api.post<CountSheet>(`/count/${day}/skip`, { reason })
    skipOpen.value = false
    toasts.success(day === date.value ? t('count.skippedToast') : t('count.previousSkippedToast'))
    await load()
  } catch (e) {
    toasts.error(
      e instanceof ApiError && e.status === 0 ? t('errors.network') : t('errors.generic'),
    )
  } finally {
    skipping.value = false
  }
}

async function reopen() {
  try {
    sheet.value = await api.post<CountSheet>(`/count/${date.value}/reopen`)
    items.value = withPending(sheet.value.items, sync.queue, date.value)
  } catch {
    toasts.error(t('errors.generic'))
  }
}

watch(date, load, { immediate: true })
</script>

<template>
  <AppPage
    :title="$t('count.title')"
    :eyebrow="isToday ? undefined : $t('count.catchingUp')"
    :subtitle="
      formatDate(date, 'long') +
      (sheet?.closesAt ? ' · ' + $t('count.closesAt', { time: sheet.closesAt }) : '')
    "
    :back="isToday ? undefined : { name: 'count' }"
    :back-label="$t('count.backToToday')"
  >
    <template #meta>
      <UiBadge v-if="sheet && items.length" tone="neutral" class="num">{{
        $t('count.progress', { counted: summary.counted, total: summary.total })
      }}</UiBadge>
      <SyncBadge :state="sync.state" :waiting="waitingHere" />
      <UiButton
        v-if="status === 'OPEN' && sheet?.shopOpen"
        variant="inverse"
        size="sm"
        :icon="PhSkipForward"
        class="skip"
        @click="skipOpen = true"
      >
        {{ isToday ? $t('count.skip') : $t('count.skipThisDay') }}
      </UiButton>
    </template>

    <UiSkeleton v-if="loading && !sheet" card :lines="6" />

    <UiEmpty v-else-if="failed" :icon="PhStorefront" :title="$t('errors.generic')">
      <UiButton variant="secondary" @click="load">{{ $t('common.retry') }}</UiButton>
    </UiEmpty>

    <template v-else-if="sheet">
      <UiNotice v-if="fromCache" tone="warning" :title="$t('count.offlineTitle')">{{
        $t('count.offlineText')
      }}</UiNotice>

      <UiNotice
        v-if="previousMissing"
        tone="warning"
        :title="$t('count.previousTitle', { day: formatDate(previousMissing.date, 'long') })"
      >
        {{
          previousMissing.counted > 0
            ? $t('count.previousPartial', { n: previousMissing.counted })
            : $t('count.previousText')
        }}
        <template #actions>
          <UiButton
            size="sm"
            variant="secondary"
            :to="{ name: 'count', query: { date: previousMissing.date } }"
            >{{ $t('count.previousCount') }}</UiButton
          >
          <UiButton
            size="sm"
            variant="ghost"
            :loading="skipping"
            @click="skip($t('count.skipReasons.forgot'), previousMissing.date)"
            >{{ $t('count.previousSkip') }}</UiButton
          >
        </template>
      </UiNotice>

      <section v-if="status === 'SKIPPED'" class="state">
        <span class="state__icon"
          ><PhCalendarX :size="28" weight="duotone" aria-hidden="true"
        /></span>
        <div class="state__text">
          <h2>{{ $t('count.skippedTitle') }}</h2>
          <p class="muted">
            {{
              $t('count.skippedText', {
                reason: sheet.skipReason ?? '—',
                name: sheet.closedBy ?? '—',
              })
            }}
          </p>
        </div>
        <UiButton variant="secondary" :icon="PhArrowCounterClockwise" @click="reopen">{{
          $t('count.undoSkip')
        }}</UiButton>
      </section>

      <CountDone
        v-else-if="status === 'COUNTED' && !editing"
        :sheet="sheet"
        :summary="summary"
        :items="items"
        @edit="editing = true"
      />

      <UiEmpty
        v-else-if="!items.length"
        :icon="PhStorefront"
        :title="sheet.shopOpen ? $t('count.emptyTitle') : $t('count.closedTitle')"
        :text="sheet.shopOpen ? $t('count.emptyText') : $t('count.closedText')"
      >
        <UiButton v-if="auth.hasRole('OWNER')" variant="secondary" :to="{ name: 'products' }">{{
          $t('nav.products')
        }}</UiButton>
      </UiEmpty>

      <div v-if="showList" class="layout">
        <div class="layout__list">
          <section
            v-for="g in groups"
            :key="g.category"
            class="group"
            :aria-labelledby="`cat-${g.category}`"
          >
            <header class="group__head">
              <span class="group__icon"
                ><component
                  :is="CATEGORY_ICONS[g.category]"
                  :size="18"
                  weight="duotone"
                  aria-hidden="true"
              /></span>
              <h2 :id="`cat-${g.category}`" class="group__title">
                {{ $t(`categories.${g.category}`) }}
              </h2>
              <span class="group__count num"
                >{{ g.items.filter((i) => i.left !== null).length }}/{{ g.items.length }}</span
              >
            </header>
            <CountRow
              v-for="item in g.items"
              :key="item.productId"
              :item="item"
              @change="(left, soldOutAt) => change(item, left, soldOutAt)"
              @sold-out="openSoldOut(item)"
              @next="focusNext(item)"
            />
          </section>
          <p class="hint hint--phone">{{ $t('count.hint') }}</p>

          <div class="bar">
            <div class="bar__progress">
              <div class="bar__numbers">
                <span class="num strong"
                  >{{ summary.counted }}<span class="subtle">/{{ summary.total }}</span></span
                >
                <span class="small muted">{{ $t('count.barLeft', { n: summary.leftUnits }) }}</span>
              </div>
              <UiProgress
                :value="summary.counted"
                :max="summary.total || 1"
                :label="$t('count.progress', { counted: summary.counted, total: summary.total })"
                :tone="summary.counted === summary.total ? 'success' : 'primary'"
              />
            </div>
            <UiButton size="lg" :icon="PhCheckFat" :loading="finishing" @click="finish">
              {{ status === 'COUNTED' ? $t('count.saveChanges') : $t('count.finish') }}
            </UiButton>
          </div>
        </div>

        <aside class="side" :aria-label="$t('count.side.title')">
          <p class="eyebrow">{{ $t('count.side.title') }}</p>
          <p class="side__big num">
            {{ summary.leftUnits }}<span>{{ $t('count.side.left') }}</span>
          </p>
          <UiProgress
            :value="summary.counted"
            :max="summary.total || 1"
            :label="$t('count.progress', { counted: summary.counted, total: summary.total })"
            :tone="summary.counted === summary.total ? 'success' : 'primary'"
          />
          <dl class="side__list">
            <div>
              <dt>{{ $t('count.side.counted') }}</dt>
              <dd class="num">{{ summary.counted }}/{{ summary.total }}</dd>
            </div>
            <div>
              <dt>{{ $t('count.side.wasted') }}</dt>
              <dd class="num">{{ formatMoney(summary.wasteCents, { decimals: true }) }}</dd>
            </div>
            <div>
              <dt>{{ $t('count.side.soldOut') }}</dt>
              <dd class="num">{{ summary.soldOut }}</dd>
            </div>
          </dl>
          <UiButton block size="lg" :icon="PhCheckFat" :loading="finishing" @click="finish">
            {{ status === 'COUNTED' ? $t('count.saveChanges') : $t('count.finish') }}
          </UiButton>
          <p class="hint">{{ $t('count.hint') }}</p>
        </aside>
      </div>
    </template>

    <SoldOutDialog
      v-model:open="soldOutOpen"
      :item="soldOutItem"
      :opens-at="sheet?.opensAt ?? null"
      :closes-at="sheet?.closesAt ?? null"
      @pick="pickSoldOut"
    />
    <SkipDialog v-model:open="skipOpen" :busy="skipping" @skip="(reason) => skip(reason)" />
  </AppPage>
</template>

<style scoped>
.skip {
  margin-left: 4px;
}
.layout {
  display: grid;
  gap: 20px;
  align-items: start;
}
.layout__list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-width: 0;
}
.hint {
  font-size: var(--text-sm);
  color: var(--text-muted);
}
.hint--phone {
  padding: 0 4px;
  text-align: center;
}
.side {
  display: none;
}
@media (min-width: 1000px) {
  .layout {
    grid-template-columns: minmax(0, 1fr) 300px;
  }
  .side {
    position: sticky;
    top: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 20px;
    background:
      radial-gradient(
        420px 180px at 100% 0%,
        color-mix(in srgb, var(--accent-300) 28%, transparent),
        transparent 70%
      ),
      var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg), var(--highlight);
  }
}
.side__big {
  display: flex;
  align-items: baseline;
  gap: 8px;
  font-family: var(--font-display);
  font-size: 2.6rem;
  font-weight: 650;
  line-height: 1;
  color: var(--primary-strong);
}
.side__big span {
  font-family: var(--font-body);
  font-size: var(--text-md);
  font-weight: 600;
  color: var(--text-muted);
}
.side__list {
  display: flex;
  flex-direction: column;
  margin: 0;
}
.side__list div {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px dashed var(--border-strong);
  font-size: var(--text-sm);
}
.side__list div:last-child {
  border-bottom: 0;
}
.side__list dt {
  color: var(--text-muted);
}
.side__list dd {
  margin: 0;
  font-weight: 700;
}
.group {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md), var(--highlight);
  overflow: hidden;
}
.group__head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: linear-gradient(180deg, var(--surface-muted), var(--surface));
  border-bottom: 1px solid var(--border);
}
.group__icon {
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border-radius: 9px;
  color: var(--primary);
  background: var(--primary-soft);
}
.group__title {
  flex: 1;
  font-size: var(--text-lg);
}
.group__count {
  font-size: var(--text-sm);
  font-weight: 700;
  color: var(--text-subtle);
}
.group :deep(.row + .row) {
  border-top: 1px solid var(--border);
}
.bar {
  position: sticky;
  bottom: 16px;
  z-index: 20;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 12px 12px 18px;
  background: color-mix(in srgb, var(--surface) 92%, transparent);
  backdrop-filter: blur(12px) saturate(1.3);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xl), var(--highlight);
}
.bar__progress {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.bar__numbers {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  font-size: var(--text-lg);
}
.state {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
  padding: 22px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md), var(--highlight);
}
.state__icon {
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  color: var(--warning-text);
  background: var(--warning-soft);
}
.state__text {
  flex: 1;
  min-width: 200px;
}
@media (max-width: 899px) {
  .bar {
    bottom: calc(var(--bottom-nav-h) + env(safe-area-inset-bottom) + 10px);
  }
}
/* On wide screens the side panel carries the progress and the finish button. */
@media (min-width: 1000px) {
  .bar,
  .hint--phone {
    display: none;
  }
}
@media (max-width: 480px) {
  .bar {
    gap: 12px;
    padding: 10px 10px 10px 14px;
  }
}
</style>
