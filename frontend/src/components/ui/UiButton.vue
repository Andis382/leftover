<script setup lang="ts">
import { computed, type Component } from 'vue'
import { RouterLink, type RouteLocationRaw } from 'vue-router'
import UiSpinner from './UiSpinner.vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'soft' | 'danger' | 'inverse'
    size?: 'sm' | 'md' | 'lg'
    type?: 'button' | 'submit' | 'reset'
    to?: RouteLocationRaw
    href?: string
    target?: string
    icon?: Component
    iconRight?: Component
    loading?: boolean
    disabled?: boolean
    block?: boolean
  }>(),
  {
    variant: 'primary',
    size: 'md',
    type: 'button',
    to: undefined,
    href: undefined,
    target: undefined,
    icon: undefined,
    iconRight: undefined,
  },
)

const tag = computed(() => (props.to ? RouterLink : props.href ? 'a' : 'button'))
const isButton = computed(() => !props.to && !props.href)
</script>

<template>
  <component
    :is="tag"
    class="btn"
    :class="[
      `btn--${variant}`,
      `btn--${size}`,
      { 'btn--block': block, 'is-loading': loading, 'is-disabled': disabled },
    ]"
    :to="to"
    :href="href"
    :target="target"
    :rel="target === '_blank' ? 'noopener' : undefined"
    :type="isButton ? type : undefined"
    :disabled="isButton ? disabled || loading : undefined"
    :aria-disabled="!isButton && disabled ? 'true' : undefined"
    :aria-busy="loading ? 'true' : undefined"
  >
    <UiSpinner v-if="loading" size="1.1em" />
    <component
      :is="icon"
      v-else-if="icon"
      class="btn__icon"
      :size="size === 'lg' ? 22 : 18"
      weight="bold"
      aria-hidden="true"
    />
    <span v-if="$slots.default" class="btn__label"><slot /></span>
    <component
      :is="iconRight"
      v-if="iconRight"
      class="btn__icon"
      :size="size === 'lg' ? 22 : 18"
      weight="bold"
      aria-hidden="true"
    />
  </component>
</template>

<style scoped>
.btn {
  --btn-h: 44px;
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: var(--btn-h);
  padding: 0 18px;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  font-family: var(--font-body);
  font-size: var(--text-sm);
  font-weight: 650;
  line-height: 1;
  letter-spacing: 0.005em;
  text-decoration: none;
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
  transition:
    transform var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease),
    background-color var(--duration) var(--ease),
    border-color var(--duration) var(--ease),
    color var(--duration) var(--ease);
}
.btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 4px var(--focus-ring);
}
.btn:active:not(:disabled) {
  transform: translateY(0) scale(0.99);
}
.btn:disabled,
.btn.is-disabled {
  cursor: not-allowed;
  opacity: 0.55;
  transform: none;
}
.btn.is-loading {
  cursor: progress;
}

.btn--sm {
  --btn-h: 36px;
  padding: 0 12px;
  font-size: var(--text-xs);
  gap: 6px;
  border-radius: var(--radius-xs);
}
.btn--lg {
  --btn-h: 54px;
  padding: 0 24px;
  font-size: var(--text-md);
  border-radius: var(--radius);
}
.btn--block {
  display: flex;
  width: 100%;
}

/* Primary: gradient body, inner highlight, coloured shadow, lifts on hover */
.btn--primary {
  color: var(--on-primary);
  background: linear-gradient(180deg, var(--brand-500) 0%, var(--brand-600) 100%);
  border-color: var(--brand-700);
  box-shadow:
    inset 0 1px 0 rgb(255 255 255 / 0.22),
    var(--primary-shadow);
  text-shadow: 0 1px 0 rgb(0 0 0 / 0.12);
}
.btn--primary:hover:not(:disabled) {
  color: var(--on-primary);
  background: linear-gradient(180deg, var(--brand-500) 0%, var(--brand-700) 100%);
  transform: translateY(-1px);
  box-shadow:
    inset 0 1px 0 rgb(255 255 255 / 0.22),
    0 2px 4px rgb(16 24 40 / 0.14),
    0 10px 22px -8px color-mix(in srgb, var(--brand-600) 80%, transparent);
}
.btn--primary:focus-visible {
  box-shadow:
    inset 0 1px 0 rgb(255 255 255 / 0.22),
    var(--primary-shadow),
    0 0 0 4px var(--focus-ring);
}

.btn--secondary {
  color: var(--text);
  background: linear-gradient(180deg, #ffffff 0%, var(--gray-25) 100%);
  border-color: var(--border-strong);
  box-shadow: var(--shadow-xs), var(--highlight);
}
.btn--secondary:hover:not(:disabled) {
  color: var(--text);
  border-color: var(--gray-300);
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm), var(--highlight);
}

.btn--soft {
  color: var(--primary-soft-text);
  background: var(--primary-soft);
  border-color: var(--primary-soft-border);
}
.btn--soft:hover:not(:disabled) {
  color: var(--primary-strong);
  background: color-mix(in srgb, var(--primary-soft) 70%, var(--brand-100));
  transform: translateY(-1px);
}

.btn--ghost {
  color: var(--text-muted);
  background: transparent;
}
.btn--ghost:hover:not(:disabled) {
  color: var(--text);
  background: var(--surface-sunken);
}

.btn--danger {
  color: #fff;
  background: linear-gradient(180deg, #e25454 0%, var(--danger) 100%);
  border-color: var(--danger-hover);
  box-shadow:
    inset 0 1px 0 rgb(255 255 255 / 0.2),
    0 1px 2px rgb(16 24 40 / 0.14),
    0 6px 16px -6px rgb(210 59 59 / 0.6);
}
.btn--danger:hover:not(:disabled) {
  color: #fff;
  transform: translateY(-1px);
  background: linear-gradient(180deg, #d94848 0%, var(--danger-hover) 100%);
}

.btn--inverse {
  color: var(--header-text);
  background: rgb(255 255 255 / 0.12);
  border-color: rgb(255 255 255 / 0.18);
  box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.12);
  backdrop-filter: blur(6px);
}
.btn--inverse:hover:not(:disabled) {
  color: var(--header-text);
  background: rgb(255 255 255 / 0.2);
  transform: translateY(-1px);
}

.btn__icon {
  flex: none;
}
.btn__label {
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
