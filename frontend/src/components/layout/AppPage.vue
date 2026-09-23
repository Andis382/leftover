<script setup lang="ts">
import { RouterLink, type RouteLocationRaw } from 'vue-router'
import { PhArrowLeft } from '@phosphor-icons/vue'

/**
 * Every signed-in screen: the title block renders inside the shell's dark band,
 * the content below overlaps its lower edge.
 */
withDefaults(
  defineProps<{
    title: string
    subtitle?: string
    eyebrow?: string
    back?: RouteLocationRaw
    backLabel?: string
  }>(),
  { subtitle: undefined, eyebrow: undefined, back: undefined, backLabel: undefined },
)
</script>

<template>
  <Teleport defer to="#page-hero">
    <div class="hero">
      <RouterLink v-if="back" :to="back" class="hero__back">
        <PhArrowLeft :size="16" weight="bold" aria-hidden="true" />
        {{ backLabel ?? $t('common.back') }}
      </RouterLink>
      <div class="hero__row">
        <div class="hero__titles">
          <p v-if="eyebrow" class="hero__eyebrow">{{ eyebrow }}</p>
          <h1 class="hero__title">{{ title }}</h1>
          <p v-if="subtitle" class="hero__subtitle">{{ subtitle }}</p>
          <div v-if="$slots.meta" class="hero__meta"><slot name="meta" /></div>
        </div>
        <div v-if="$slots.actions" class="hero__actions"><slot name="actions" /></div>
      </div>
    </div>
  </Teleport>
  <div class="page">
    <slot />
  </div>
</template>

<style scoped>
.hero {
  padding: 8px 0 4px;
  animation: rise 260ms var(--ease);
}
.hero__back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 10px;
  padding: 4px 10px 4px 8px;
  border-radius: var(--radius-pill);
  color: var(--text-inverse-muted);
  font-size: var(--text-sm);
  font-weight: 600;
  text-decoration: none;
  background: rgb(255 255 255 / 0.06);
}
.hero__back:hover {
  color: var(--header-text);
  background: rgb(255 255 255 / 0.12);
}
.hero__row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px 24px;
  flex-wrap: wrap;
}
.hero__titles {
  min-width: 0;
  max-width: 720px;
}
.hero__eyebrow {
  margin-bottom: 6px;
  font-size: var(--text-xs);
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-inverse-muted);
}
.hero__title {
  color: var(--header-text);
  font-size: clamp(1.7rem, 1.25rem + 1.7vw, 2.5rem);
  font-weight: 620;
  font-variation-settings:
    'SOFT' 100,
    'WONK' 0;
  letter-spacing: -0.02em;
  text-shadow: 0 1px 0 rgb(0 0 0 / 0.12);
  overflow-wrap: anywhere;
}
.hero__subtitle {
  margin-top: 8px;
  font-size: var(--text-md);
  color: var(--text-inverse-muted);
  max-width: 62ch;
}
.hero__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}
.hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
@media (max-width: 640px) {
  .hero__actions {
    width: 100%;
  }
  .hero__actions :deep(.btn) {
    flex: 1;
  }
}
@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
}
</style>
