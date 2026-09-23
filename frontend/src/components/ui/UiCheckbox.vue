<script setup lang="ts">
import { PhCheck } from '@phosphor-icons/vue'

const model = defineModel<boolean>({ default: false })

withDefaults(defineProps<{ id?: string; label?: string; hint?: string; disabled?: boolean }>(), {
  id: undefined,
  label: undefined,
  hint: undefined,
})
</script>

<template>
  <label class="check" :class="{ 'check--disabled': disabled }">
    <input :id="id" v-model="model" type="checkbox" class="check__native" :disabled="disabled" />
    <span class="check__box" aria-hidden="true"><PhCheck :size="14" weight="bold" /></span>
    <span class="check__text">
      <span class="check__label"
        ><slot>{{ label }}</slot></span
      >
      <span v-if="hint" class="check__hint">{{ hint }}</span>
    </span>
  </label>
</template>

<style scoped>
.check {
  display: inline-flex;
  align-items: flex-start;
  gap: 12px;
  min-height: 28px;
  cursor: pointer;
}
.check--disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
.check__native {
  position: absolute;
  opacity: 0;
  width: 1px;
  height: 1px;
}
.check__box {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  flex: none;
  margin-top: 1px;
  color: transparent;
  background: var(--surface);
  border: 1.5px solid var(--gray-300);
  border-radius: 7px;
  box-shadow: var(--shadow-xs);
  transition:
    background-color var(--duration) var(--ease),
    border-color var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease);
}
.check:hover .check__box {
  border-color: var(--primary);
}
.check__native:checked + .check__box {
  color: #fff;
  background: linear-gradient(180deg, var(--brand-500), var(--brand-600));
  border-color: var(--brand-700);
  box-shadow:
    inset 0 1px 0 rgb(255 255 255 / 0.2),
    0 2px 6px -2px var(--focus-ring);
}
.check__native:focus-visible + .check__box {
  box-shadow: 0 0 0 4px var(--focus-ring);
}
.check__text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.check__label {
  font-size: var(--text-sm);
  font-weight: 550;
  color: var(--text);
}
.check__hint {
  font-size: var(--text-xs);
  color: var(--text-subtle);
}
</style>
