<script setup lang="ts" generic="T extends string | number | null">
import { PhCaretDown } from '@phosphor-icons/vue'

defineOptions({ inheritAttrs: false })

const model = defineModel<T>()

withDefaults(
  defineProps<{
    id?: string
    options: { value: T; label: string; disabled?: boolean }[]
    placeholder?: string
    invalid?: boolean
    describedby?: string
  }>(),
  { id: undefined, placeholder: undefined, describedby: undefined },
)
</script>

<template>
  <div class="select" :class="{ 'select--invalid': invalid }">
    <select
      :id="id"
      v-model="model"
      v-bind="$attrs"
      class="select__native"
      :aria-invalid="invalid ? 'true' : undefined"
      :aria-describedby="describedby"
    >
      <option v-if="placeholder" :value="null" disabled>{{ placeholder }}</option>
      <option v-for="o in options" :key="String(o.value)" :value="o.value" :disabled="o.disabled">
        {{ o.label }}
      </option>
    </select>
    <PhCaretDown class="select__caret" :size="16" weight="bold" aria-hidden="true" />
  </div>
</template>

<style scoped>
.select {
  position: relative;
  display: flex;
}
.select__native {
  width: 100%;
  min-height: 46px;
  padding: 0 40px 0 14px;
  appearance: none;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: inset 0 1px 2px rgb(16 24 40 / 0.04);
  cursor: pointer;
  transition:
    border-color var(--duration) var(--ease),
    background-color var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease);
}
.select__native:hover {
  border-color: var(--border-strong);
}
.select__native:focus {
  outline: none;
  background: var(--surface);
  border-color: var(--primary);
  box-shadow: 0 0 0 4px var(--focus-ring);
}
.select--invalid .select__native {
  border-color: var(--danger);
}
.select__caret {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--text-subtle);
}
</style>
