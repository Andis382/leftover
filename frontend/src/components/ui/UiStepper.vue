<script setup lang="ts">
import { PhMinus, PhPlus } from '@phosphor-icons/vue'

const model = defineModel<number | null>({ default: null })

const props = withDefaults(
  defineProps<{
    id?: string
    label: string
    min?: number
    max?: number
    step?: number
    size?: 'md' | 'lg'
    disabled?: boolean
    /** what the phone keyboard's Enter key says, e.g. "next" in a list of counts */
    enterkeyhint?: 'next' | 'done' | 'enter'
  }>(),
  { id: undefined, min: 0, max: 99999, step: 1, size: 'md', enterkeyhint: undefined },
)

defineEmits<{ enter: [] }>()

function change(delta: number) {
  const current = model.value ?? 0
  model.value = Math.min(props.max, Math.max(props.min, current + delta))
}

function onInput(e: Event) {
  const raw = (e.target as HTMLInputElement).value
  if (raw === '') {
    model.value = null
    return
  }
  const n = Math.round(Number(raw))
  if (Number.isFinite(n)) model.value = Math.min(props.max, Math.max(props.min, n))
}
</script>

<template>
  <div class="stepper" :class="`stepper--${size}`">
    <button
      type="button"
      class="stepper__btn"
      :aria-label="`${label} −${step}`"
      :disabled="disabled || (model ?? 0) <= min"
      @click="change(-step)"
    >
      <PhMinus :size="size === 'lg' ? 22 : 18" weight="bold" aria-hidden="true" />
    </button>
    <input
      :id="id"
      class="stepper__input"
      type="number"
      inputmode="numeric"
      :min="min"
      :max="max"
      :value="model ?? ''"
      :aria-label="label"
      :disabled="disabled"
      :enterkeyhint="enterkeyhint"
      placeholder="–"
      @input="onInput"
      @focus="($event.target as HTMLInputElement).select()"
      @keydown.enter.prevent="$emit('enter')"
    />
    <button
      type="button"
      class="stepper__btn"
      :aria-label="`${label} +${step}`"
      :disabled="disabled || (model ?? 0) >= max"
      @click="change(step)"
    >
      <PhPlus :size="size === 'lg' ? 22 : 18" weight="bold" aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
.stepper {
  --s: 44px;
  display: inline-flex;
  align-items: stretch;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-xs), var(--highlight);
  overflow: hidden;
}
.stepper--lg {
  --s: 54px;
}
.stepper__btn {
  display: grid;
  place-items: center;
  width: var(--s);
  min-height: var(--s);
  border: 0;
  background: var(--surface-muted);
  color: var(--text);
  transition: background-color var(--duration) var(--ease);
}
.stepper__btn:hover:not(:disabled) {
  background: var(--primary-soft);
  color: var(--primary-strong);
}
.stepper__btn:active:not(:disabled) {
  background: var(--brand-100);
}
.stepper__btn:disabled {
  color: var(--gray-300);
  cursor: not-allowed;
}
.stepper__btn:focus-visible {
  outline: none;
  box-shadow: inset 0 0 0 3px var(--focus-ring);
}
.stepper__input {
  width: calc(var(--s) * 1.35);
  border: 0;
  border-inline: 1px solid var(--border);
  text-align: center;
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  background: var(--surface);
  -moz-appearance: textfield;
  appearance: textfield;
}
.stepper--lg .stepper__input {
  font-size: var(--text-xl);
}
.stepper__input::-webkit-inner-spin-button,
.stepper__input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.stepper__input:focus {
  outline: none;
  background: var(--primary-soft);
}
.stepper__input::placeholder {
  color: var(--gray-300);
}
.stepper__input:disabled {
  color: var(--text-muted);
  background: var(--surface-muted);
}
</style>
