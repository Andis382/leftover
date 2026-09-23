<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { PhCalendarX, PhCheckCircle, PhOven, PhSealCheck } from '@phosphor-icons/vue'
import AppPage from '@/components/layout/AppPage.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import UiNotice from '@/components/ui/UiNotice.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import UiStepper from '@/components/ui/UiStepper.vue'
import { api, ApiError } from '@/lib/api'
import { CATEGORY_ICONS } from '@/lib/categories'
import { groupByCategory } from '@/lib/count'
import { shopNow } from '@/lib/dates'
import { formatDate, formatTime } from '@/lib/format'
import { formatChange } from '@/lib/plan'
import { useAuth } from '@/stores/auth'
import { useToasts } from '@/stores/toasts'
import type { Plan } from '@/types'

/** 5 a.m.: what actually went into the oven. Defaults to the plan; change only what differs. */
const { t, locale } = useI18n()
const auth = useAuth()
const toasts = useToasts()

const today = computed(() => shopNow(auth.organization?.timezone).day)
const plan = ref<Plan | null>(null)
const baked = ref<Record<number, number>>({})
const loading = ref(true)
const failed = ref(false)
const saving = ref(false)

const groups = computed(() => groupByCategory(plan.value?.rows ?? []))
const total = computed(() => Object.values(baked.value).reduce((sum, n) => sum + n, 0))
// Differences are shown against the plan's own suggestion, the same reference as the plan screen.
const changed = computed(
  () => (plan.value?.rows ?? []).filter((r) => baked.value[r.productId] !== r.suggested).length,
)
const confirmed = computed(() => !!plan.value?.bakedConfirmedAt)
const dirty = computed(() =>
  (plan.value?.rows ?? []).some((r) => baked.value[r.productId] !== r.willBake),
)

async function load() {
  loading.value = true
  failed.value = false
  try {
    plan.value = await api.get<Plan>(`/plans/${today.value}`)
    baked.value = Object.fromEntries(plan.value.rows.map((r) => [r.productId, r.willBake]))
  } catch {
    failed.value = true
  } finally {
    loading.value = false
  }
}

async function confirm(asPlanned = false) {
  if (!plan.value) return
  if (asPlanned)
    baked.value = Object.fromEntries(plan.value.rows.map((r) => [r.productId, r.willBake]))
  saving.value = true
  try {
    plan.value = await api.post<Plan>(`/plans/${today.value}/baked`, {
      items: plan.value.rows.map((r) => ({
        productId: r.productId,
        baked: baked.value[r.productId] ?? r.willBake,
      })),
    })
    baked.value = Object.fromEntries(plan.value.rows.map((r) => [r.productId, r.willBake]))
    toasts.success(t('morning.toast'), t('morning.toastText', { n: total.value }))
  } catch (e) {
    toasts.error(
      e instanceof ApiError && e.status === 0 ? t('errors.network') : t('errors.generic'),
    )
  } finally {
    saving.value = false
  }
}

watch(locale, load)
load()
</script>

<template>
  <AppPage :title="$t('morning.title')" :subtitle="formatDate(today, 'long')">
    <template #meta>
      <UiBadge v-if="plan && confirmed" tone="success" :icon="PhSealCheck">
        {{
          $t('morning.confirmedAt', {
            time: formatTime(plan.bakedConfirmedAt),
            name: plan.bakedConfirmedBy ?? '—',
          })
        }}
      </UiBadge>
      <UiBadge v-else-if="plan && plan.generated" tone="neutral">{{
        $t('plan.madeAt', { time: formatTime(plan.generatedAt) })
      }}</UiBadge>
    </template>

    <UiSkeleton v-if="loading && !plan" card :lines="8" />

    <UiEmpty v-else-if="failed" :icon="PhOven" :title="$t('errors.generic')">
      <UiButton variant="secondary" @click="load">{{ $t('common.retry') }}</UiButton>
    </UiEmpty>

    <UiEmpty v-else-if="plan && !plan.shopOpen" :icon="PhCalendarX" :title="$t('morning.closed')" />

    <UiEmpty v-else-if="plan && !plan.rows.length" :icon="PhOven" :title="$t('plan.emptyTitle')" />

    <template v-else-if="plan">
      <UiNotice
        v-if="!plan.generated"
        tone="info"
        :title="$t('morning.noPlan', { time: formatTime(plan.finalAt) })"
        >{{ $t('morning.noPlanText') }}</UiNotice
      >
      <section class="intro">
        <span class="intro__icon"><PhOven :size="22" weight="duotone" aria-hidden="true" /></span>
        <p class="intro__text">{{ $t('morning.intro') }}</p>
        <UiButton
          v-if="!confirmed"
          variant="secondary"
          :icon="PhCheckCircle"
          :loading="saving"
          @click="confirm(true)"
          >{{ $t('morning.allAsPlanned') }}</UiButton
        >
      </section>

      <section v-for="g in groups" :key="g.category" class="group">
        <header class="group__head">
          <component
            :is="CATEGORY_ICONS[g.category]"
            :size="18"
            weight="duotone"
            aria-hidden="true"
          />
          <h2>{{ $t(`categories.${g.category}`) }}</h2>
        </header>
        <div
          v-for="row in g.items"
          :key="row.productId"
          class="line"
          :class="{ 'is-changed': baked[row.productId] !== row.suggested }"
        >
          <div class="line__info">
            <h3 class="line__name">{{ row.name }}</h3>
            <p class="line__plan">
              {{ $t('morning.planned', { n: row.suggested }) }}
              <span v-if="baked[row.productId] !== row.suggested" class="line__diff num">{{
                formatChange((baked[row.productId] ?? 0) - row.suggested)
              }}</span>
            </p>
          </div>
          <UiStepper
            :model-value="baked[row.productId] ?? row.willBake"
            size="lg"
            :step="1"
            :label="$t('morning.bakedLabel', { name: row.name })"
            @update:model-value="(v) => (baked[row.productId] = v ?? 0)"
          />
        </div>
      </section>

      <div class="bar">
        <div class="bar__text">
          <p class="strong num">{{ $t('morning.total', { n: total }) }}</p>
          <p class="small muted">
            {{ changed ? $t('morning.changed', { n: changed }, changed) : $t('morning.asPlanned') }}
          </p>
        </div>
        <UiButton
          size="lg"
          :icon="PhSealCheck"
          :loading="saving"
          :disabled="confirmed && !dirty"
          @click="confirm()"
        >
          {{ confirmed ? $t('morning.update') : $t('morning.confirm') }}
        </UiButton>
      </div>
    </template>
  </AppPage>
</template>

<style scoped>
.intro {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 16px;
  padding: 16px 18px;
  background:
    radial-gradient(
      500px 160px at 100% 0%,
      color-mix(in srgb, var(--accent-300) 30%, transparent),
      transparent 70%
    ),
    var(--paper);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md), var(--highlight);
}
.intro__icon {
  display: grid;
  place-items: center;
  flex: none;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  color: var(--accent-soft-text);
  background: var(--accent-soft);
}
.intro__text {
  flex: 1;
  min-width: 220px;
  font-size: var(--text-sm);
  color: var(--text-muted);
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
  color: var(--primary);
  background: linear-gradient(180deg, var(--surface-muted), var(--surface));
  border-bottom: 1px solid var(--border);
}
.group__head h2 {
  font-size: var(--text-lg);
}
.line {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  transition: background-color var(--duration) var(--ease);
}
.line + .line {
  border-top: 1px solid var(--border);
}
.line.is-changed {
  background: color-mix(in srgb, var(--accent-soft) 60%, transparent);
}
.line__info {
  flex: 1;
  min-width: 0;
}
.line__name {
  font-family: var(--font-body);
  font-size: 1.02rem;
  font-weight: 700;
}
.line__plan {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 2px;
  font-size: var(--text-sm);
  color: var(--text-subtle);
}
.line__diff {
  padding: 1px 8px;
  border-radius: var(--radius-pill);
  background: var(--accent-soft);
  color: var(--accent-soft-text);
  font-weight: 750;
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
.bar__text {
  flex: 1;
  min-width: 0;
}
@media (max-width: 899px) {
  .bar {
    bottom: calc(var(--bottom-nav-h) + env(safe-area-inset-bottom) + 10px);
  }
}
</style>
