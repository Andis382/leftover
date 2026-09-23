<script setup lang="ts">
import { useId } from 'vue'

withDefaults(defineProps<{ size?: number; withName?: boolean; inverse?: boolean }>(), {
  size: 34,
  withName: true,
  inverse: true,
})

// A loaf whose scoring is a tally of five: counting what is left is the whole idea.
const crust = `crust-${useId()}`
const crumb = `crumb-${useId()}`
</script>

<template>
  <span class="brand" :class="{ 'brand--inverse': inverse }">
    <svg :width="size" :height="size" viewBox="0 0 40 40" aria-hidden="true" class="brand__mark">
      <defs>
        <linearGradient :id="crust" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stop-color="#df7433" />
          <stop offset="1" stop-color="#8c3816" />
        </linearGradient>
        <linearGradient :id="crumb" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#fff3dc" />
          <stop offset="1" stop-color="#f4d49c" />
        </linearGradient>
      </defs>
      <rect x="1" y="1" width="38" height="38" rx="11" :fill="`url(#${crust})`" />
      <rect
        x="1.5"
        y="1.5"
        width="37"
        height="37"
        rx="10.5"
        fill="none"
        stroke="#fff"
        stroke-opacity=".28"
      />
      <path
        d="M8.5 27.4C8.5 19.6 13.6 13.8 20 13.8s11.5 5.8 11.5 13.6c0 1.2-.9 2.1-2.1 2.1H10.6c-1.2 0-2.1-.9-2.1-2.1Z"
        :fill="`url(#${crumb})`"
      />
      <g stroke="#b04c1c" stroke-width="1.9" stroke-linecap="round" fill="none">
        <path d="M14.3 19.9v5.6M17.9 18.6v6.9M21.5 18.6v6.9M25.1 19.9v5.6" />
        <path d="M12.3 25.1 27.4 19.4" />
      </g>
    </svg>
    <span v-if="withName" class="brand__name">{{ $t('app.name') }}</span>
  </span>
</template>

<style scoped>
.brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--text);
  text-decoration: none;
}
.brand--inverse {
  color: var(--header-text);
}
.brand__mark {
  flex: none;
  filter: drop-shadow(0 4px 10px rgb(0 0 0 / 0.28));
}
.brand__name {
  font-family: var(--font-display);
  font-size: 1.25rem;
  font-weight: 680;
  font-variation-settings:
    'SOFT' 100,
    'WONK' 1;
  letter-spacing: -0.01em;
}
</style>
