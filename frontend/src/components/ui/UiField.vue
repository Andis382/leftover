<script setup lang="ts">
import { computed } from 'vue'
import { PhWarningCircle } from '@phosphor-icons/vue'

const props = defineProps<{
  id: string
  label?: string
  hint?: string
  error?: string | string[] | null
  required?: boolean
  optional?: boolean
}>()

const errorText = computed(
  () => (Array.isArray(props.error) ? props.error[0] : props.error) || null,
)
const describedby = computed(
  () =>
    [props.hint ? `${props.id}-hint` : null, errorText.value ? `${props.id}-error` : null]
      .filter(Boolean)
      .join(' ') || undefined,
)
</script>

<template>
  <div class="field" :class="{ 'field--invalid': !!errorText }">
    <div v-if="label || $slots.aside" class="field__top">
      <label v-if="label" :for="id" class="field__label">
        {{ label }}
        <span v-if="required" class="field__required" aria-hidden="true">*</span>
        <span v-if="optional" class="field__optional">({{ $t('common.optional') }})</span>
      </label>
      <slot name="aside" />
    </div>
    <slot :id="id" :describedby="describedby" :invalid="!!errorText" />
    <p v-if="hint && !errorText" :id="`${id}-hint`" class="field__hint">{{ hint }}</p>
    <p v-if="errorText" :id="`${id}-error`" class="field__error">
      <PhWarningCircle weight="fill" :size="16" aria-hidden="true" />
      <span>{{ errorText }}</span>
    </p>
  </div>
</template>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}
.field__top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
}
.field__label {
  font-size: var(--text-sm);
  font-weight: 650;
  color: var(--text);
}
.field__required {
  color: var(--danger);
  margin-left: 2px;
}
.field__optional {
  font-weight: 500;
  color: var(--text-subtle);
  margin-left: 4px;
}
.field__hint {
  font-size: var(--text-xs);
  color: var(--text-subtle);
}
.field__error {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: var(--text-sm);
  font-weight: 550;
  color: var(--danger-text);
}
.field__error :deep(svg) {
  margin-top: 2px;
  flex: none;
}
</style>
