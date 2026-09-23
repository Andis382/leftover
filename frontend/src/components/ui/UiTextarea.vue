<script setup lang="ts">
defineOptions({ inheritAttrs: false })

const model = defineModel<string | null | undefined>()

withDefaults(
  defineProps<{ id?: string; invalid?: boolean; describedby?: string; rows?: number }>(),
  {
    id: undefined,
    describedby: undefined,
    rows: 3,
  },
)
</script>

<template>
  <textarea
    :id="id"
    v-model="model"
    v-bind="$attrs"
    :rows="rows"
    class="textarea"
    :class="{ 'textarea--invalid': invalid }"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-describedby="describedby"
  />
</template>

<style scoped>
.textarea {
  width: 100%;
  min-height: 92px;
  padding: 12px 14px;
  resize: vertical;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: inset 0 1px 2px rgb(16 24 40 / 0.04);
  line-height: 1.5;
  transition:
    border-color var(--duration) var(--ease),
    background-color var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease);
}
.textarea::placeholder {
  color: var(--text-subtle);
}
.textarea:hover {
  border-color: var(--border-strong);
}
.textarea:focus {
  outline: none;
  background: var(--surface);
  border-color: var(--primary);
  box-shadow: 0 0 0 4px var(--focus-ring);
}
.textarea--invalid {
  border-color: var(--danger);
}
</style>
