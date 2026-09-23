<script setup lang="ts">
import { computed } from 'vue'
import { initials } from '@/lib/format'

const props = withDefaults(defineProps<{ name: string | null | undefined; size?: number }>(), {
  size: 36,
})

// Stable hue per name so the same person always gets the same colour.
const hue = computed(() => {
  let h = 0
  for (const ch of props.name ?? '') h = (h * 31 + ch.charCodeAt(0)) % 360
  return h
})
</script>

<template>
  <span
    class="avatar"
    :style="{
      width: `${size}px`,
      height: `${size}px`,
      fontSize: `${Math.round(size * 0.38)}px`,
      '--h': hue,
    }"
    aria-hidden="true"
    >{{ initials(name) }}</span
  >
</template>

<style scoped>
.avatar {
  display: inline-grid;
  place-items: center;
  flex: none;
  border-radius: 50%;
  font-family: var(--font-display);
  font-weight: 700;
  letter-spacing: 0.02em;
  color: hsl(var(--h) 45% 26%);
  background: linear-gradient(160deg, hsl(var(--h) 70% 92%), hsl(var(--h) 55% 84%));
  box-shadow:
    inset 0 1px 0 rgb(255 255 255 / 0.7),
    0 1px 2px rgb(16 24 40 / 0.1);
}
</style>
