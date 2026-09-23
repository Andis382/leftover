<script setup lang="ts">
import { computed } from 'vue'

/**
 * Small, dependency-free bar chart. Bars can be stacked (e.g. sold + left over).
 * A visually hidden table carries the same numbers for screen readers.
 */
type Series = { key: string; label: string; color: string }
type Row = { label: string; values: Record<string, number>; highlight?: boolean }

const props = withDefaults(
  defineProps<{
    title: string
    series: Series[]
    rows: Row[]
    height?: number
    format?: (n: number) => string
  }>(),
  { height: 180, format: (n: number) => String(Math.round(n)) },
)

const max = computed(() => {
  const totals = props.rows.map((r) =>
    props.series.reduce((sum, s) => sum + (r.values[s.key] ?? 0), 0),
  )
  const m = Math.max(1, ...totals)
  // round the scale up to a friendly number
  const magnitude = 10 ** Math.floor(Math.log10(m))
  return Math.ceil(m / magnitude) * magnitude
})

const ticks = computed(() => [0, 0.5, 1].map((f) => ({ f, value: max.value * f })))
</script>

<template>
  <figure class="bars" :aria-label="title">
    <div class="bars__plot" :style="{ height: `${height}px` }" aria-hidden="true">
      <div class="bars__grid">
        <div v-for="t in ticks" :key="t.f" class="bars__tick" :style="{ bottom: `${t.f * 100}%` }">
          <span>{{ format(t.value) }}</span>
        </div>
      </div>
      <div class="bars__cols">
        <div
          v-for="r in rows"
          :key="r.label"
          class="bars__col"
          :class="{ 'bars__col--hl': r.highlight }"
        >
          <div class="bars__stack">
            <div
              v-for="s in series"
              :key="s.key"
              class="bars__seg"
              :style="{ height: `${((r.values[s.key] ?? 0) / max) * 100}%`, background: s.color }"
              :title="`${s.label}: ${format(r.values[s.key] ?? 0)}`"
            />
          </div>
          <span class="bars__label">{{ r.label }}</span>
        </div>
      </div>
    </div>
    <figcaption v-if="series.length > 1" class="bars__legend">
      <span v-for="s in series" :key="s.key" class="bars__key"
        ><i :style="{ background: s.color }" />{{ s.label }}</span
      >
    </figcaption>
    <table class="visually-hidden">
      <caption>
        {{
          title
        }}
      </caption>
      <thead>
        <tr>
          <th scope="col"></th>
          <th v-for="s in series" :key="s.key" scope="col">{{ s.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in rows" :key="r.label">
          <th scope="row">{{ r.label }}</th>
          <td v-for="s in series" :key="s.key">{{ format(r.values[s.key] ?? 0) }}</td>
        </tr>
      </tbody>
    </table>
  </figure>
</template>

<style scoped>
.bars {
  margin: 0;
}
.bars__plot {
  position: relative;
  padding-left: 36px;
  padding-bottom: 26px;
}
.bars__grid {
  position: absolute;
  inset: 0 0 26px 36px;
}
.bars__tick {
  position: absolute;
  left: -36px;
  right: 0;
  border-top: 1px dashed var(--border);
}
.bars__tick span {
  position: absolute;
  left: 0;
  top: -9px;
  width: 30px;
  text-align: right;
  font-size: 11px;
  color: var(--text-subtle);
  font-variant-numeric: tabular-nums;
}
.bars__cols {
  position: relative;
  display: flex;
  align-items: stretch;
  gap: 6px;
  height: 100%;
}
.bars__col {
  position: relative;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  min-width: 0;
}
.bars__stack {
  display: flex;
  flex-direction: column-reverse;
  height: 100%;
  justify-content: flex-start;
}
.bars__seg {
  width: 100%;
  min-height: 0;
  transition: height 400ms var(--ease);
}
.bars__seg:first-child {
  border-radius: 0 0 4px 4px;
}
.bars__seg:last-child {
  border-radius: 6px 6px 0 0;
}
.bars__seg:only-child {
  border-radius: 6px 6px 4px 4px;
}
.bars__col--hl .bars__stack {
  filter: saturate(1.15);
}
.bars__label {
  position: absolute;
  bottom: -24px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 11px;
  font-weight: 600;
  color: var(--text-subtle);
  white-space: nowrap;
}
.bars__col--hl .bars__label {
  color: var(--text);
}
.bars__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 12px;
  font-size: var(--text-xs);
  color: var(--text-muted);
}
.bars__key {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.bars__key i {
  width: 10px;
  height: 10px;
  border-radius: 3px;
}
</style>
