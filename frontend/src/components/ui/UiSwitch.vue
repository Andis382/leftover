<script setup lang="ts">
const model = defineModel<boolean>({ default: false })

withDefaults(defineProps<{ id?: string; label?: string; hint?: string; disabled?: boolean }>(), {
  id: undefined,
  label: undefined,
  hint: undefined,
})
</script>

<template>
  <label class="switch" :class="{ 'switch--disabled': disabled }">
    <span class="switch__text">
      <span class="switch__label"
        ><slot>{{ label }}</slot></span
      >
      <span v-if="hint" class="switch__hint">{{ hint }}</span>
    </span>
    <input
      :id="id"
      v-model="model"
      type="checkbox"
      role="switch"
      class="switch__native"
      :disabled="disabled"
    />
    <span class="switch__track" aria-hidden="true"><span class="switch__thumb" /></span>
  </label>
</template>

<style scoped>
.switch {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  min-height: 44px;
  cursor: pointer;
}
.switch--disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.switch__text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.switch__label {
  font-size: var(--text-sm);
  font-weight: 600;
}
.switch__hint {
  font-size: var(--text-xs);
  color: var(--text-subtle);
}
.switch__native {
  position: absolute;
  opacity: 0;
  width: 1px;
  height: 1px;
}
.switch__track {
  position: relative;
  width: 46px;
  height: 28px;
  flex: none;
  background: var(--gray-200);
  border-radius: var(--radius-pill);
  box-shadow: inset 0 1px 3px rgb(16 24 40 / 0.14);
  transition: background-color var(--duration) var(--ease);
}
.switch__thumb {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 22px;
  height: 22px;
  background: #fff;
  border-radius: 50%;
  box-shadow:
    0 1px 2px rgb(16 24 40 / 0.2),
    0 2px 6px rgb(16 24 40 / 0.12);
  transition: transform var(--duration) var(--ease);
}
.switch__native:checked + .switch__track {
  background: linear-gradient(180deg, var(--brand-500), var(--brand-600));
}
.switch__native:checked + .switch__track .switch__thumb {
  transform: translateX(18px);
}
.switch__native:focus-visible + .switch__track {
  box-shadow: 0 0 0 4px var(--focus-ring);
}
</style>
