<script setup lang="ts">
withDefaults(defineProps<{ size?: string; label?: string }>(), { size: '1.25em', label: undefined })
</script>

<template>
  <span
    class="spinner"
    :style="{ width: size, height: size }"
    role="status"
    :aria-label="label"
    :aria-hidden="label ? undefined : 'true'"
  >
    <svg viewBox="0 0 24 24" fill="none">
      <circle
        cx="12"
        cy="12"
        r="9.5"
        stroke="currentColor"
        stroke-opacity="0.22"
        stroke-width="3"
      />
      <path
        d="M21.5 12a9.5 9.5 0 0 0-9.5-9.5"
        stroke="currentColor"
        stroke-width="3"
        stroke-linecap="round"
      />
    </svg>
  </span>
</template>

<style scoped>
.spinner {
  display: inline-flex;
  flex: none;
}
.spinner svg {
  width: 100%;
  height: 100%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
