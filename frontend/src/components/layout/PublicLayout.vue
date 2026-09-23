<script setup lang="ts">
import LanguageSwitch from './LanguageSwitch.vue'
import BrandMark from './BrandMark.vue'

/** Pages customers open from a WhatsApp link: no login, the business's name first. */
withDefaults(defineProps<{ business?: string | null; subtitle?: string | null }>(), {
  business: null,
  subtitle: null,
})
</script>

<template>
  <div class="public">
    <header class="public__band">
      <div class="public__bar">
        <div class="public__who">
          <p class="public__business">{{ business ?? $t('app.name') }}</p>
          <p v-if="subtitle" class="public__subtitle">{{ subtitle }}</p>
        </div>
        <LanguageSwitch inverse />
      </div>
    </header>
    <main class="public__main">
      <slot />
    </main>
    <footer class="public__foot">
      <BrandMark :size="20" :inverse="false" />
    </footer>
  </div>
</template>

<style scoped>
.public {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}
.public__band {
  padding: 18px var(--gutter) 88px;
  color: var(--header-text);
  background:
    radial-gradient(700px 300px at 10% -20%, var(--header-glow), transparent 65%),
    linear-gradient(120deg, var(--header-from), var(--header-via) 55%, var(--header-to));
}
.public__bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  max-width: 640px;
  margin: 0 auto;
}
.public__business {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 800;
  letter-spacing: -0.02em;
}
.public__subtitle {
  font-size: var(--text-sm);
  color: var(--text-inverse-muted);
}
.public__main {
  flex: 1;
  width: 100%;
  max-width: calc(640px + 2 * var(--gutter));
  margin: -64px auto 0;
  padding: 0 var(--gutter) 40px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.public__foot {
  display: flex;
  justify-content: center;
  padding: 16px 0 32px;
  opacity: 0.7;
}
.public__foot :deep(.brand__name) {
  font-size: var(--text-sm);
}
</style>
