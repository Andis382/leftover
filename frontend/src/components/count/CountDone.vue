<script setup lang="ts">
import { computed } from 'vue'
import { PhArrowRight, PhClockCountdown, PhPencilSimple, PhSealCheck } from '@phosphor-icons/vue'
import UiButton from '@/components/ui/UiButton.vue'
import { formatMoney, formatTime } from '@/lib/format'
import type { CountItem, CountSheet, CountSummary } from '@/types'

/** After "Finish count": what the day left behind, in three numbers and two short lists. */
const props = defineProps<{ sheet: CountSheet; summary: CountSummary; items: CountItem[] }>()
defineEmits<{ edit: [] }>()

const soldOut = computed(() =>
  props.items
    .filter((i) => i.soldOutAt)
    .sort((a, b) => (a.soldOutAt ?? '').localeCompare(b.soldOutAt ?? '')),
)
const mostLeft = computed(() =>
  props.items
    .filter((i) => (i.left ?? 0) > 0)
    .sort((a, b) => (b.left ?? 0) * b.wasteValueCents - (a.left ?? 0) * a.wasteValueCents)
    .slice(0, 4),
)
const wastePct = computed(() =>
  props.summary.bakedUnits > 0
    ? Math.round((props.summary.leftUnits / props.summary.bakedUnits) * 100)
    : null,
)
</script>

<template>
  <section class="done">
    <header class="done__head">
      <span class="done__seal"><PhSealCheck :size="30" weight="fill" aria-hidden="true" /></span>
      <div>
        <h2 class="done__title">{{ $t('count.doneTitle') }}</h2>
        <p class="muted">
          {{
            $t('count.doneText', { name: sheet.closedBy ?? '—', time: formatTime(sheet.closedAt) })
          }}
        </p>
      </div>
    </header>

    <dl class="done__figures">
      <div class="fig">
        <dt>{{ $t('count.summary.left') }}</dt>
        <dd class="num">{{ summary.leftUnits }}</dd>
        <p v-if="wastePct !== null" class="fig__sub">
          {{ $t('count.summary.ofBaked', { pct: wastePct }) }}
        </p>
      </div>
      <div class="fig fig--waste">
        <dt>{{ $t('count.summary.wasted') }}</dt>
        <dd class="num">{{ formatMoney(summary.wasteCents, { decimals: true }) }}</dd>
        <p class="fig__sub">{{ $t('count.summary.atCost') }}</p>
      </div>
      <div class="fig fig--sold">
        <dt>{{ $t('count.summary.soldOuts') }}</dt>
        <dd class="num">{{ summary.soldOut }}</dd>
        <p class="fig__sub">
          {{ $t('count.summary.counted', { counted: summary.counted, total: summary.total }) }}
        </p>
      </div>
    </dl>

    <div class="done__lists">
      <div v-if="mostLeft.length">
        <p class="eyebrow">{{ $t('count.summary.mostLeft') }}</p>
        <ul class="list">
          <li v-for="i in mostLeft" :key="i.productId">
            <span>{{ i.name }}</span
            ><span class="num strong">{{ i.left }}</span>
          </li>
        </ul>
      </div>
      <div v-if="soldOut.length">
        <p class="eyebrow">{{ $t('count.summary.soldOutList') }}</p>
        <ul class="list">
          <li v-for="i in soldOut" :key="i.productId">
            <span>{{ i.name }}</span>
            <span class="list__time"
              ><PhClockCountdown :size="14" weight="bold" aria-hidden="true" />{{
                i.soldOutAt
              }}</span
            >
          </li>
        </ul>
      </div>
    </div>

    <footer class="done__actions">
      <UiButton variant="secondary" :icon="PhPencilSimple" @click="$emit('edit')">{{
        $t('count.editCounts')
      }}</UiButton>
      <UiButton :icon-right="PhArrowRight" :to="{ name: 'plan' }">{{
        $t('count.seePlan')
      }}</UiButton>
    </footer>
  </section>
</template>

<style scoped>
.done {
  display: flex;
  flex-direction: column;
  gap: 22px;
  padding: clamp(20px, 3vw, 28px);
  background:
    radial-gradient(
      600px 220px at 0% 0%,
      color-mix(in srgb, var(--accent-300) 30%, transparent),
      transparent 70%
    ),
    var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg), var(--highlight);
}
.done__head {
  display: flex;
  gap: 14px;
  align-items: center;
}
.done__seal {
  display: grid;
  place-items: center;
  flex: none;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  color: var(--success);
  background: var(--success-soft);
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--success) 25%, transparent);
}
.done__title {
  font-size: var(--text-2xl);
}
.done__figures {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin: 0;
}
.fig {
  padding: 14px 16px;
  border-radius: var(--radius);
  background: var(--surface-muted);
  border: 1px solid var(--border);
}
.fig dt {
  font-size: var(--text-xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-subtle);
}
.fig dd {
  margin: 4px 0 0;
  font-family: var(--font-display);
  font-size: clamp(1.6rem, 1.2rem + 1.5vw, 2.3rem);
  font-weight: 650;
  line-height: 1.1;
  color: var(--text);
}
.fig--waste dd {
  color: var(--primary-strong);
}
.fig--sold dd {
  color: var(--accent-soft-text);
}
.fig__sub {
  margin-top: 4px;
  font-size: var(--text-xs);
  color: var(--text-subtle);
}
.done__lists {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr));
  gap: 18px;
}
.list {
  margin: 8px 0 0;
  padding: 0;
  list-style: none;
}
.list li {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px dashed var(--border-strong);
  font-size: var(--text-sm);
}
.list li:last-child {
  border-bottom: 0;
}
.list__time {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: var(--accent-soft-text);
  font-weight: 650;
  font-variant-numeric: tabular-nums;
}
.done__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
@media (max-width: 560px) {
  .done__figures {
    grid-template-columns: 1fr 1fr;
  }
  .fig--sold {
    grid-column: 1 / -1;
  }
  .done__actions :deep(.btn) {
    flex: 1;
  }
}
</style>
