<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, type Component } from 'vue'
import { PhDotsThreeVertical } from '@phosphor-icons/vue'

export type MenuItem = {
  label: string
  icon?: Component
  danger?: boolean
  disabled?: boolean
  action: () => void
}

withDefaults(
  defineProps<{ items: MenuItem[]; label: string; icon?: Component; align?: 'left' | 'right' }>(),
  {
    icon: undefined,
    align: 'right',
  },
)

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const menu = ref<HTMLElement | null>(null)

function outside(e: MouseEvent) {
  if (root.value && !root.value.contains(e.target as Node)) close()
}

async function toggle() {
  open.value = !open.value
  if (open.value) {
    document.addEventListener('mousedown', outside)
    await nextTick()
    ;(menu.value?.querySelector('button:not(:disabled)') as HTMLElement | null)?.focus()
  } else {
    document.removeEventListener('mousedown', outside)
  }
}

function close() {
  open.value = false
  document.removeEventListener('mousedown', outside)
}

function run(item: MenuItem) {
  close()
  item.action()
}

function onKey(e: KeyboardEvent) {
  if (!menu.value) return
  const buttons = Array.from(menu.value.querySelectorAll<HTMLElement>('button:not(:disabled)'))
  const i = buttons.indexOf(document.activeElement as HTMLElement)
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    buttons[(i + 1) % buttons.length]?.focus()
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    buttons[(i - 1 + buttons.length) % buttons.length]?.focus()
  } else if (e.key === 'Escape') {
    close()
    ;(root.value?.querySelector('.menu__trigger') as HTMLElement | null)?.focus()
  }
}

onBeforeUnmount(() => document.removeEventListener('mousedown', outside))
</script>

<template>
  <div ref="root" class="menu" @keydown="onKey">
    <button
      type="button"
      class="menu__trigger"
      :aria-label="label"
      :title="label"
      aria-haspopup="menu"
      :aria-expanded="open"
      @click="toggle"
    >
      <component :is="icon ?? PhDotsThreeVertical" :size="20" weight="bold" aria-hidden="true" />
    </button>
    <Transition name="menu">
      <div v-if="open" ref="menu" class="menu__panel" :class="`menu__panel--${align}`" role="menu">
        <button
          v-for="item in items"
          :key="item.label"
          type="button"
          role="menuitem"
          class="menu__item"
          :class="{ 'menu__item--danger': item.danger }"
          :disabled="item.disabled"
          @click="run(item)"
        >
          <component :is="item.icon" v-if="item.icon" :size="18" weight="bold" aria-hidden="true" />
          <span>{{ item.label }}</span>
        </button>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.menu {
  position: relative;
  display: inline-flex;
}
.menu__trigger {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border: 0;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--text-muted);
}
.menu__trigger:hover,
.menu__trigger[aria-expanded='true'] {
  background: var(--surface-sunken);
  color: var(--text);
}
.menu__panel {
  position: absolute;
  z-index: 40;
  top: calc(100% + 6px);
  min-width: 200px;
  padding: 6px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-xl), var(--highlight);
}
.menu__panel--right {
  right: 0;
}
.menu__panel--left {
  left: 0;
}
.menu__item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  min-height: 40px;
  padding: 0 12px;
  border: 0;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--text);
  font-size: var(--text-sm);
  font-weight: 550;
  text-align: left;
}
.menu__item:hover:not(:disabled),
.menu__item:focus-visible {
  outline: none;
  background: var(--surface-sunken);
}
.menu__item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.menu__item--danger {
  color: var(--danger-text);
}
.menu__item--danger:hover:not(:disabled) {
  background: var(--danger-soft);
}
.menu-enter-active,
.menu-leave-active {
  transition:
    opacity 140ms var(--ease),
    transform 140ms var(--ease);
}
.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}
</style>
