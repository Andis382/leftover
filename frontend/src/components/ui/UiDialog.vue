<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import { PhX } from '@phosphor-icons/vue'

/**
 * Native <dialog>: modal focus trap, Escape and an inert background come from the browser.
 * On phones it slides up as a bottom sheet.
 */
const open = defineModel<boolean>('open', { default: false })

withDefaults(defineProps<{ title: string; description?: string; size?: 'sm' | 'md' | 'lg' }>(), {
  description: undefined,
  size: 'md',
})

const emit = defineEmits<{ close: [] }>()
const el = ref<HTMLDialogElement | null>(null)

watch(
  open,
  async (value) => {
    await nextTick()
    const dialog = el.value
    if (!dialog) return
    if (value && !dialog.open) dialog.showModal()
    if (!value && dialog.open) dialog.close()
  },
  { immediate: true },
)

function onClose() {
  open.value = false
  emit('close')
}

function onBackdrop(e: MouseEvent) {
  if (e.target === el.value) onClose()
}
</script>

<template>
  <dialog
    ref="el"
    class="dialog"
    :class="`dialog--${size}`"
    :aria-labelledby="'dlg-title'"
    @close="onClose"
    @cancel.prevent="onClose"
    @click="onBackdrop"
  >
    <div class="dialog__panel">
      <header class="dialog__header">
        <div>
          <h2 id="dlg-title" class="dialog__title">{{ title }}</h2>
          <p v-if="description" class="dialog__desc">{{ description }}</p>
        </div>
        <button
          type="button"
          class="dialog__close"
          :aria-label="$t('common.close')"
          @click="onClose"
        >
          <PhX :size="20" weight="bold" aria-hidden="true" />
        </button>
      </header>
      <div class="dialog__body">
        <slot />
      </div>
      <footer v-if="$slots.footer" class="dialog__footer">
        <slot name="footer" />
      </footer>
    </div>
  </dialog>
</template>

<style scoped>
.dialog {
  width: min(560px, calc(100vw - 32px));
  max-height: calc(100dvh - 48px);
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--text);
  overflow: visible;
}
.dialog--sm {
  width: min(420px, calc(100vw - 32px));
}
.dialog--lg {
  width: min(760px, calc(100vw - 32px));
}
.dialog::backdrop {
  background: rgb(10 16 22 / 0.48);
  backdrop-filter: blur(3px);
}
.dialog[open] {
  animation: pop 180ms var(--ease);
}
.dialog__panel {
  display: flex;
  flex-direction: column;
  max-height: calc(100dvh - 48px);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl), var(--highlight);
  overflow: hidden;
}
.dialog__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 22px 24px 8px;
}
.dialog__title {
  font-size: var(--text-xl);
}
.dialog__desc {
  margin-top: 4px;
  font-size: var(--text-sm);
  color: var(--text-muted);
}
.dialog__close {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  margin: -6px -8px 0 0;
  border: 0;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--text-subtle);
  flex: none;
}
.dialog__close:hover {
  background: var(--surface-sunken);
  color: var(--text);
}
.dialog__body {
  padding: 12px 24px 24px;
  overflow-y: auto;
}
.dialog__footer {
  display: flex;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  background: var(--surface-muted);
}
@media (max-width: 640px) {
  .dialog {
    width: 100vw;
    max-width: 100vw;
    margin: auto 0 0;
  }
  .dialog__panel {
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
    padding-bottom: env(safe-area-inset-bottom);
  }
  .dialog[open] {
    animation: sheet 220ms var(--ease);
  }
  .dialog__footer :deep(.btn) {
    flex: 1;
  }
}
@keyframes pop {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
}
@keyframes sheet {
  from {
    transform: translateY(40px);
    opacity: 0;
  }
}
</style>
