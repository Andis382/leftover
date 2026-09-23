<script setup lang="ts">
withDefaults(defineProps<{ lines?: number; height?: string; card?: boolean }>(), {
  lines: 3,
  height: '14px',
})
</script>

<template>
  <div class="skeleton" :class="{ 'skeleton--card': card }" aria-hidden="true">
    <span
      v-for="i in lines"
      :key="i"
      class="skeleton__line"
      :style="{ height, width: i === lines && lines > 1 ? '62%' : '100%' }"
    />
  </div>
</template>

<style scoped>
.skeleton {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.skeleton--card {
  padding: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}
.skeleton__line {
  display: block;
  border-radius: 6px;
  background: linear-gradient(
    90deg,
    var(--surface-sunken) 0%,
    var(--surface-muted) 50%,
    var(--surface-sunken) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.3s ease-in-out infinite;
}
@keyframes shimmer {
  from {
    background-position: 100% 0;
  }
  to {
    background-position: -100% 0;
  }
}
</style>
