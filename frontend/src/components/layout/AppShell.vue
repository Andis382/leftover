<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { PhCaretDown, PhDotsThreeOutline, PhGearSix, PhSignOut } from '@phosphor-icons/vue'
import BrandMark from './BrandMark.vue'
import LanguageSwitch from './LanguageSwitch.vue'
import UiAvatar from '@/components/ui/UiAvatar.vue'
import UiDialog from '@/components/ui/UiDialog.vue'
import { NAV } from '@/nav'
import { useAuth } from '@/stores/auth'
import { useCountSync } from '@/stores/countSync'

const auth = useAuth()
const countSync = useCountSync()

// Counts saved on this phone while offline (or before a reload) go out as soon as the app opens.
onMounted(() => countSync.flush())
const route = useRoute()
const router = useRouter()

const items = computed(() => NAV.filter((i) => !i.roles || auth.hasRole(...i.roles)))
const primary = computed(() => items.value.filter((i) => i.primary).slice(0, 4))
const topBar = computed(() => items.value.filter((i) => i.topBar !== false))
const moreOpen = ref(false)
const userOpen = ref(false)
const userRoot = ref<HTMLElement | null>(null)

function isActive(name: string) {
  return (
    route.name === name || route.meta.nav === name || route.matched.some((r) => r.name === name)
  )
}

async function signOut() {
  userOpen.value = false
  moreOpen.value = false
  await auth.logout()
  await router.push({ name: 'login' })
}

function outside(e: MouseEvent) {
  if (userRoot.value && !userRoot.value.contains(e.target as Node)) userOpen.value = false
}

watch(userOpen, (open) => {
  if (open) document.addEventListener('mousedown', outside)
  else document.removeEventListener('mousedown', outside)
})
watch(
  () => route.fullPath,
  () => {
    moreOpen.value = false
    userOpen.value = false
  },
)
onBeforeUnmount(() => document.removeEventListener('mousedown', outside))
</script>

<template>
  <div class="shell">
    <a href="#main" class="skip-link">{{ $t('common.skipToContent') }}</a>

    <header class="band">
      <div class="band__bar wrap">
        <RouterLink :to="{ name: 'home' }" class="band__brand" :aria-label="$t('app.name')">
          <BrandMark />
        </RouterLink>

        <nav class="band__nav" :aria-label="$t('common.menu')">
          <RouterLink
            v-for="i in topBar"
            :key="i.name"
            :to="{ name: i.name }"
            class="navlink"
            :class="{ 'is-active': isActive(i.name) }"
            :aria-current="isActive(i.name) ? 'page' : undefined"
          >
            <component
              :is="i.icon"
              :size="18"
              :weight="isActive(i.name) ? 'fill' : 'bold'"
              aria-hidden="true"
            />
            <span>{{ $t(i.label) }}</span>
          </RouterLink>
        </nav>

        <div class="band__tools">
          <LanguageSwitch inverse class="band__lang" />
          <div ref="userRoot" class="user">
            <button
              type="button"
              class="user__btn"
              :aria-expanded="userOpen"
              aria-haspopup="menu"
              @click="userOpen = !userOpen"
            >
              <UiAvatar :name="auth.user?.name" :size="34" />
              <span class="user__who">
                <span class="user__name">{{ auth.user?.name }}</span>
                <span class="user__org">{{ auth.organization?.name }}</span>
              </span>
              <PhCaretDown class="user__caret" :size="14" weight="bold" aria-hidden="true" />
            </button>
            <Transition name="drop">
              <div v-if="userOpen" class="user__menu" role="menu">
                <div class="user__head">
                  <p class="strong">{{ auth.user?.name }}</p>
                  <p class="small muted truncate">{{ auth.user?.email }}</p>
                </div>
                <RouterLink :to="{ name: 'settings' }" class="user__item" role="menuitem">
                  <PhGearSix :size="18" weight="bold" aria-hidden="true" /> {{ $t('nav.settings') }}
                </RouterLink>
                <button
                  type="button"
                  class="user__item user__item--danger"
                  role="menuitem"
                  @click="signOut"
                >
                  <PhSignOut :size="18" weight="bold" aria-hidden="true" />
                  {{ $t('common.signOut') }}
                </button>
              </div>
            </Transition>
          </div>
        </div>
      </div>
      <div id="page-hero" class="band__hero wrap" />
    </header>

    <main id="main" class="shell__main wrap" tabindex="-1">
      <RouterView />
    </main>

    <nav class="tabbar" :aria-label="$t('common.menu')">
      <RouterLink
        v-for="i in primary"
        :key="i.name"
        :to="{ name: i.name }"
        class="tabbar__item"
        :class="{ 'is-active': isActive(i.name) }"
        :aria-current="isActive(i.name) ? 'page' : undefined"
      >
        <span class="tabbar__icon"
          ><component
            :is="i.icon"
            :size="22"
            :weight="isActive(i.name) ? 'fill' : 'regular'"
            aria-hidden="true"
        /></span>
        <span class="tabbar__label">{{ $t(i.label) }}</span>
      </RouterLink>
      <button type="button" class="tabbar__item" :aria-expanded="moreOpen" @click="moreOpen = true">
        <span class="tabbar__icon"><PhDotsThreeOutline :size="22" aria-hidden="true" /></span>
        <span class="tabbar__label">{{ $t('common.more') }}</span>
      </button>
    </nav>

    <UiDialog
      v-model:open="moreOpen"
      :title="auth.organization?.name ?? $t('common.more')"
      :description="auth.user?.name"
    >
      <nav class="sheet-nav" :aria-label="$t('common.more')">
        <RouterLink
          v-for="i in items"
          :key="i.name"
          :to="{ name: i.name }"
          class="sheet-nav__item"
          :class="{ 'is-active': isActive(i.name) }"
        >
          <span class="sheet-nav__icon"
            ><component :is="i.icon" :size="20" weight="duotone" aria-hidden="true"
          /></span>
          {{ $t(i.label) }}
        </RouterLink>
      </nav>
      <div class="sheet-foot">
        <LanguageSwitch />
        <button type="button" class="sheet-signout" @click="signOut">
          <PhSignOut :size="18" weight="bold" aria-hidden="true" /> {{ $t('common.signOut') }}
        </button>
      </div>
    </UiDialog>
  </div>
</template>

<style scoped>
.shell {
  min-height: 100dvh;
  padding-bottom: calc(var(--bottom-nav-h) + env(safe-area-inset-bottom) + 24px);
}
@media (min-width: 900px) {
  .shell {
    padding-bottom: 48px;
  }
}
.wrap {
  width: 100%;
  max-width: calc(var(--content-max) + 2 * var(--gutter));
  margin-inline: auto;
  padding-inline: var(--gutter);
}

/* The band: one element, one gradient, so the bar and the page title read as one surface */
.band {
  position: relative;
  color: var(--header-text);
  background:
    radial-gradient(900px 320px at 12% -10%, var(--header-glow), transparent 65%),
    radial-gradient(700px 260px at 95% 0%, rgb(255 255 255 / 0.07), transparent 60%),
    linear-gradient(120deg, var(--header-from) 0%, var(--header-via) 55%, var(--header-to) 100%);
  padding-bottom: 64px;
  isolation: isolate;
}
.band::before {
  /* fine dot texture */
  content: '';
  position: absolute;
  inset: 0;
  z-index: -1;
  background-image: radial-gradient(rgb(255 255 255 / 0.07) 1px, transparent 1px);
  background-size: 18px 18px;
  mask-image: linear-gradient(180deg, #000 0%, transparent 85%);
}
.band::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgb(255 255 255 / 0.18), transparent);
}
.band__bar {
  display: flex;
  align-items: center;
  gap: 20px;
  min-height: 68px;
}
.band__brand {
  display: inline-flex;
  border-radius: var(--radius-sm);
  text-decoration: none;
}
.band__brand:focus-visible {
  outline: 3px solid rgb(255 255 255 / 0.5);
  outline-offset: 4px;
}
.band__nav {
  display: flex;
  gap: 4px;
  flex: 1;
  min-width: 0;
  overflow-x: auto;
  scrollbar-width: none;
}
.navlink {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 40px;
  padding: 0 14px;
  border-radius: var(--radius-pill);
  color: var(--text-inverse-muted);
  font-size: var(--text-sm);
  font-weight: 650;
  text-decoration: none;
  white-space: nowrap;
  transition:
    background-color var(--duration) var(--ease),
    color var(--duration) var(--ease);
}
.navlink:hover {
  color: var(--header-text);
  background: rgb(255 255 255 / 0.08);
}
.navlink svg {
  flex: none;
}
.navlink.is-active {
  color: var(--header-from);
  background: rgb(255 255 255 / 0.94);
  box-shadow:
    0 1px 2px rgb(0 0 0 / 0.18),
    0 6px 16px -6px rgb(0 0 0 / 0.35);
}
.navlink:focus-visible {
  outline: 3px solid rgb(255 255 255 / 0.55);
  outline-offset: 2px;
}
.band__tools {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-left: auto;
}
.user {
  position: relative;
}
.user__btn {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 46px;
  padding: 0 10px 0 6px;
  border: 1px solid rgb(255 255 255 / 0.14);
  border-radius: var(--radius-pill);
  background: rgb(255 255 255 / 0.07);
  color: var(--header-text);
  transition: background-color var(--duration) var(--ease);
}
.user__btn:hover,
.user__btn[aria-expanded='true'] {
  background: rgb(255 255 255 / 0.14);
}
.user__btn:focus-visible {
  outline: 3px solid rgb(255 255 255 / 0.55);
  outline-offset: 2px;
}
.user__who {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  line-height: 1.15;
  max-width: 160px;
}
.user__name {
  font-size: var(--text-sm);
  font-weight: 700;
}
.user__org {
  font-size: 11px;
  color: var(--text-inverse-muted);
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.user__menu {
  position: absolute;
  z-index: 50;
  right: 0;
  top: calc(100% + 8px);
  width: 250px;
  padding: 6px;
  background: var(--surface);
  color: var(--text);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-xl), var(--highlight);
}
.user__head {
  padding: 10px 12px 12px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 6px;
}
.user__item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  min-height: 42px;
  padding: 0 12px;
  border: 0;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--text);
  font-size: var(--text-sm);
  font-weight: 600;
  text-decoration: none;
}
.user__item:hover {
  color: var(--text);
  background: var(--surface-sunken);
}
.user__item--danger {
  color: var(--danger-text);
}
.band__hero:empty {
  display: none;
}

.shell__main {
  position: relative;
  margin-top: -40px;
  outline: none;
}

/* Bottom tab bar on phones */
.tabbar {
  position: fixed;
  z-index: 30;
  left: 0;
  right: 0;
  bottom: 0;
  display: none;
  grid-auto-flow: column;
  grid-auto-columns: 1fr;
  padding: 6px 8px calc(6px + env(safe-area-inset-bottom));
  background: rgb(255 255 255 / 0.94);
  backdrop-filter: blur(14px) saturate(1.4);
  border-top: 1px solid var(--border);
  box-shadow: 0 -10px 30px -12px rgb(16 24 40 / 0.18);
}
.tabbar__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  min-height: 54px;
  border: 0;
  background: transparent;
  color: var(--text-subtle);
  font-size: 11px;
  font-weight: 650;
  text-decoration: none;
}
.tabbar__icon {
  display: grid;
  place-items: center;
  width: 52px;
  height: 30px;
  border-radius: var(--radius-pill);
  transition: background-color var(--duration) var(--ease);
}
.tabbar__item.is-active {
  color: var(--primary-strong);
}
.tabbar__item.is-active .tabbar__icon {
  background: var(--primary-soft);
}
.tabbar__item:focus-visible {
  outline: none;
}
.tabbar__item:focus-visible .tabbar__icon {
  box-shadow: 0 0 0 3px var(--focus-ring);
}

.sheet-nav {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}
.sheet-nav__item {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 56px;
  padding: 0 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text);
  font-weight: 650;
  font-size: var(--text-sm);
  text-decoration: none;
  box-shadow: var(--shadow-xs);
}
.sheet-nav__item.is-active {
  border-color: var(--primary-soft-border);
  background: var(--primary-soft);
}
.sheet-nav__icon {
  color: var(--primary);
}
.sheet-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid var(--border);
}
.sheet-signout {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 44px;
  padding: 0 14px;
  border: 1px solid color-mix(in srgb, var(--danger) 25%, transparent);
  border-radius: var(--radius-sm);
  background: var(--danger-soft);
  color: var(--danger-text);
  font-weight: 650;
}

.drop-enter-active,
.drop-leave-active {
  transition:
    opacity 140ms var(--ease),
    transform 140ms var(--ease);
}
.drop-enter-from,
.drop-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (max-width: 899px) {
  .band__nav,
  .band__lang,
  .user__who,
  .user__caret {
    display: none;
  }
  .band__bar {
    min-height: 60px;
  }
  .user__btn {
    padding: 0 4px;
    border-color: transparent;
    background: transparent;
  }
  .tabbar {
    display: grid;
  }
  .band {
    padding-bottom: 56px;
  }
}
</style>
