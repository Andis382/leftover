<script setup lang="ts">
import { PhCheckCircle, PhInfo, PhWarningCircle, PhX } from '@phosphor-icons/vue'
import { useToasts } from '@/stores/toasts'

const toasts = useToasts()
const icons = { success: PhCheckCircle, error: PhWarningCircle, info: PhInfo }
</script>

<template>
  <div class="toaster" aria-live="polite" aria-atomic="false">
    <TransitionGroup name="toast">
      <div
        v-for="t in toasts.items"
        :key="t.id"
        class="toast"
        :class="`toast--${t.tone}`"
        :role="t.tone === 'error' ? 'alert' : 'status'"
      >
        <component
          :is="icons[t.tone]"
          class="toast__icon"
          :size="22"
          weight="fill"
          aria-hidden="true"
        />
        <div class="toast__text">
          <p class="toast__title">{{ t.title }}</p>
          <p v-if="t.text" class="toast__body">{{ t.text }}</p>
        </div>
        <button
          type="button"
          class="toast__close"
          :aria-label="$t('common.close')"
          @click="toasts.dismiss(t.id)"
        >
          <PhX :size="16" weight="bold" aria-hidden="true" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toaster {
  position: fixed;
  z-index: 60;
  top: 16px;
  right: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: min(380px, calc(100vw - 32px));
  pointer-events: none;
}
@media (max-width: 640px) {
  .toaster {
    left: 16px;
    right: 16px;
    width: auto;
    top: calc(8px + env(safe-area-inset-top));
  }
}
.toast {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 14px 14px 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-xl), var(--highlight);
  pointer-events: auto;
}
.toast__icon {
  flex: none;
  margin-top: 1px;
}
.toast--success .toast__icon {
  color: var(--success);
}
.toast--error .toast__icon {
  color: var(--danger);
}
.toast--info .toast__icon {
  color: var(--info);
}
.toast--error {
  border-color: color-mix(in srgb, var(--danger) 30%, transparent);
}
.toast__text {
  flex: 1;
  min-width: 0;
}
.toast__title {
  font-size: var(--text-sm);
  font-weight: 700;
}
.toast__body {
  margin-top: 2px;
  font-size: var(--text-sm);
  color: var(--text-muted);
}
.toast__close {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border: 0;
  border-radius: var(--radius-xs);
  background: transparent;
  color: var(--text-subtle);
}
.toast__close:hover {
  background: var(--surface-sunken);
}
.toast-enter-active,
.toast-leave-active {
  transition:
    opacity 200ms var(--ease),
    transform 200ms var(--ease);
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.98);
}
</style>
