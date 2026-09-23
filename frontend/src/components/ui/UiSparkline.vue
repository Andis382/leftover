<script setup lang="ts">
import { computed, useId } from 'vue'

/** Line chart for readings over time; optional normal band (e.g. blood pressure range). */
const props = withDefaults(
  defineProps<{
    points: { x: number; y: number }[]
    height?: number
    band?: [number, number] | null
    label: string
    color?: string
  }>(),
  { height: 120, band: null, color: 'var(--primary)' },
)

const W = 600
const gradId = `spark-${useId()}`

const bounds = computed(() => {
  const ys = props.points.map((p) => p.y)
  if (props.band) ys.push(...props.band)
  const min = Math.min(...ys)
  const max = Math.max(...ys)
  const pad = (max - min || 1) * 0.15
  const xs = props.points.map((p) => p.x)
  return { minY: min - pad, maxY: max + pad, minX: Math.min(...xs), maxX: Math.max(...xs) }
})

function sx(x: number) {
  const { minX, maxX } = bounds.value
  return maxX === minX ? W / 2 : ((x - minX) / (maxX - minX)) * (W - 16) + 8
}
function sy(y: number) {
  const { minY, maxY } = bounds.value
  return props.height - ((y - minY) / (maxY - minY)) * (props.height - 16) - 8
}

const path = computed(() =>
  props.points
    .map((p, i) => `${i ? 'L' : 'M'}${sx(p.x).toFixed(1)},${sy(p.y).toFixed(1)}`)
    .join(' '),
)
const area = computed(() => {
  if (!props.points.length) return ''
  const first = props.points[0]!
  const last = props.points[props.points.length - 1]!
  return `${path.value} L${sx(last.x).toFixed(1)},${props.height} L${sx(first.x).toFixed(1)},${props.height} Z`
})
</script>

<template>
  <svg
    class="spark"
    :viewBox="`0 0 ${W} ${height}`"
    preserveAspectRatio="none"
    role="img"
    :aria-label="label"
    :style="{ height: `${height}px` }"
  >
    <defs>
      <linearGradient :id="gradId" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0%" :stop-color="color" stop-opacity="0.22" />
        <stop offset="100%" :stop-color="color" stop-opacity="0" />
      </linearGradient>
    </defs>
    <rect
      v-if="band"
      x="0"
      :y="sy(band[1])"
      :width="W"
      :height="Math.max(0, sy(band[0]) - sy(band[1]))"
      fill="var(--success-soft)"
      opacity="0.9"
    />
    <path v-if="points.length > 1" :d="area" :fill="`url(#${gradId})`" />
    <path
      v-if="points.length > 1"
      :d="path"
      fill="none"
      :stroke="color"
      stroke-width="2.5"
      stroke-linejoin="round"
      stroke-linecap="round"
      vector-effect="non-scaling-stroke"
    />
    <circle
      v-for="(p, i) in points"
      :key="i"
      :cx="sx(p.x)"
      :cy="sy(p.y)"
      r="3.5"
      :fill="color"
      stroke="#fff"
      stroke-width="1.5"
      vector-effect="non-scaling-stroke"
    />
  </svg>
</template>

<style scoped>
.spark {
  display: block;
  width: 100%;
  overflow: visible;
}
</style>
