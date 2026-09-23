<script setup lang="ts">
import { ref, type Component } from 'vue'

defineOptions({ inheritAttrs: false })

const model = defineModel<string | number | null | undefined>()

withDefaults(
  defineProps<{
    id?: string
    type?: string
    invalid?: boolean
    describedby?: string
    icon?: Component
    prefix?: string
    suffix?: string
    size?: 'md' | 'lg'
    mono?: boolean
  }>(),
  {
    id: undefined,
    type: 'text',
    describedby: undefined,
    icon: undefined,
    prefix: undefined,
    suffix: undefined,
    size: 'md',
  },
)

const input = ref<HTMLInputElement | null>(null)
defineExpose({ focus: () => input.value?.focus(), select: () => input.value?.select() })
</script>

<template>
  <div
    class="control"
    :class="[`control--${size}`, { 'control--invalid': invalid, 'control--mono': mono }]"
  >
    <component
      :is="icon"
      v-if="icon"
      class="control__icon"
      :size="18"
      weight="bold"
      aria-hidden="true"
    />
    <span v-if="prefix" class="control__affix">{{ prefix }}</span>
    <input
      :id="id"
      ref="input"
      v-model="model"
      v-bind="$attrs"
      :type="type"
      class="control__input"
      :aria-invalid="invalid ? 'true' : undefined"
      :aria-describedby="describedby"
    />
    <span v-if="suffix" class="control__affix">{{ suffix }}</span>
  </div>
</template>

<style scoped>
.control {
  --h: 46px;
  position: relative;
  display: flex;
  align-items: center;
  min-height: var(--h);
  padding: 0 14px;
  gap: 10px;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: inset 0 1px 2px rgb(16 24 40 / 0.04);
  transition:
    border-color var(--duration) var(--ease),
    background-color var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease);
}
.control--lg {
  --h: 56px;
  font-size: var(--text-lg);
}
.control:hover {
  border-color: var(--border-strong);
}
.control:focus-within {
  background: var(--surface);
  border-color: var(--primary);
  box-shadow: 0 0 0 4px var(--focus-ring);
}
.control--invalid {
  border-color: var(--danger);
  background: color-mix(in srgb, var(--danger-soft) 55%, var(--surface));
}
.control--invalid:focus-within {
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--danger) 22%, transparent);
  border-color: var(--danger);
}
.control__input {
  flex: 1;
  min-width: 0;
  height: calc(var(--h) - 2px);
  padding: 0;
  border: 0;
  background: transparent;
  outline: none;
  font-size: inherit;
  color: var(--text);
}
.control--mono .control__input {
  font-family: var(--font-mono);
  letter-spacing: 0.02em;
}
.control__input::placeholder {
  color: var(--text-subtle);
  opacity: 1;
}
.control__icon {
  flex: none;
  color: var(--text-subtle);
}
.control:focus-within .control__icon {
  color: var(--primary);
}
.control__affix {
  flex: none;
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-subtle);
}
/* number spinners add noise on desktop */
.control__input[type='number']::-webkit-inner-spin-button,
.control__input[type='number']::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.control__input[type='number'] {
  -moz-appearance: textfield;
  appearance: textfield;
  font-variant-numeric: tabular-nums;
}
</style>
