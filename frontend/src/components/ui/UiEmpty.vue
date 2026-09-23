<script setup lang="ts">
import type { Component } from 'vue'

withDefaults(defineProps<{ icon?: Component; title: string; text?: string; compact?: boolean }>(), {
  icon: undefined,
  text: undefined,
})
</script>

<template>
  <div class="empty" :class="{ 'empty--compact': compact }">
    <div v-if="icon" class="empty__art" aria-hidden="true">
      <span class="empty__ring" />
      <span class="empty__icon"
        ><component :is="icon" :size="compact ? 26 : 32" weight="duotone"
      /></span>
    </div>
    <h3 class="empty__title">{{ title }}</h3>
    <p v-if="text" class="empty__text">{{ text }}</p>
    <div v-if="$slots.default" class="empty__actions"><slot /></div>
  </div>
</template>

<style scoped>
.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
  padding: 48px 20px;
}
.empty--compact {
  padding: 28px 16px;
}
.empty__art {
  position: relative;
  display: grid;
  place-items: center;
  width: 84px;
  height: 84px;
  margin-bottom: 8px;
}
.empty--compact .empty__art {
  width: 64px;
  height: 64px;
}
.empty__ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background:
    radial-gradient(circle at 30% 25%, rgb(255 255 255 / 0.9), transparent 55%),
    linear-gradient(
      160deg,
      var(--primary-soft),
      color-mix(in srgb, var(--brand-100) 70%, var(--surface))
    );
  border: 1px solid var(--primary-soft-border);
  box-shadow:
    var(--shadow-md),
    inset 0 -4px 10px rgb(16 24 40 / 0.04);
}
.empty__icon {
  position: relative;
  color: var(--primary);
}
.empty__title {
  font-size: var(--text-lg);
}
.empty__text {
  max-width: 42ch;
  font-size: var(--text-sm);
  color: var(--text-muted);
}
.empty__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin-top: 10px;
}
</style>
