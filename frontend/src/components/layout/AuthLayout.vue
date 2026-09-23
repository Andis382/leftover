<script setup lang="ts">
import BrandMark from './BrandMark.vue'
import LanguageSwitch from './LanguageSwitch.vue'
import AuthAside from '@/components/AuthAside.vue'
</script>

<template>
  <div class="auth">
    <aside class="auth__aside">
      <BrandMark :size="40" />
      <div class="auth__pitch">
        <AuthAside />
      </div>
      <p class="auth__foot">© {{ new Date().getFullYear() }} {{ $t('app.name') }}</p>
    </aside>
    <main class="auth__main">
      <div class="auth__top">
        <span class="auth__mobile-brand"><BrandMark :size="32" :inverse="false" /></span>
        <LanguageSwitch />
      </div>
      <div class="auth__card">
        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped>
.auth {
  display: grid;
  grid-template-columns: minmax(380px, 5fr) 7fr;
  min-height: 100dvh;
}
.auth__aside {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 40px;
  padding: 40px clamp(28px, 4vw, 56px);
  color: var(--header-text);
  background:
    radial-gradient(700px 420px at 0% 0%, var(--header-glow), transparent 60%),
    radial-gradient(600px 400px at 100% 100%, rgb(255 255 255 / 0.06), transparent 60%),
    linear-gradient(150deg, var(--header-from), var(--header-via) 55%, var(--header-to));
  overflow: hidden;
  isolation: isolate;
}
.auth__aside::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: -1;
  background-image: radial-gradient(rgb(255 255 255 / 0.08) 1px, transparent 1px);
  background-size: 20px 20px;
  mask-image: radial-gradient(circle at 30% 30%, #000, transparent 75%);
}
.auth__pitch {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.auth__foot {
  font-size: var(--text-xs);
  color: var(--text-inverse-muted);
}
.auth__main {
  display: flex;
  flex-direction: column;
  padding: 28px clamp(20px, 5vw, 64px);
  background:
    radial-gradient(800px 400px at 100% 0%, var(--primary-soft), transparent 60%), var(--bg);
}
.auth__top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.auth__mobile-brand {
  visibility: hidden;
}
.auth__card {
  width: 100%;
  max-width: 440px;
  margin: auto;
  padding: 36px 0;
}
@media (max-width: 900px) {
  .auth {
    grid-template-columns: 1fr;
  }
  .auth__aside {
    display: none;
  }
  .auth__mobile-brand {
    visibility: visible;
  }
  .auth__card {
    margin-top: 24px;
  }
}
</style>
