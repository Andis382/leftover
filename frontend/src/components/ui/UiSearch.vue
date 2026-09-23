<script setup lang="ts">
import { PhMagnifyingGlass, PhX } from '@phosphor-icons/vue'

const model = defineModel<string>({ default: '' })

withDefaults(defineProps<{ placeholder?: string; label?: string }>(), {
  placeholder: undefined,
  label: undefined,
})
</script>

<template>
  <div class="search">
    <PhMagnifyingGlass class="search__icon" :size="18" weight="bold" aria-hidden="true" />
    <input
      v-model="model"
      type="search"
      class="search__input"
      :placeholder="placeholder ?? $t('common.search')"
      :aria-label="label ?? placeholder ?? $t('common.search')"
      autocomplete="off"
    />
    <button
      v-if="model"
      type="button"
      class="search__clear"
      :aria-label="$t('common.clear')"
      @click="model = ''"
    >
      <PhX :size="16" weight="bold" aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
.search {
  position: relative;
  display: flex;
  align-items: center;
  min-width: 0;
}
.search__icon {
  position: absolute;
  left: 14px;
  color: var(--text-subtle);
  pointer-events: none;
}
.search__input {
  width: 100%;
  min-height: 46px;
  padding: 0 40px 0 42px;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-pill);
  box-shadow: var(--shadow-xs), var(--highlight);
  transition:
    border-color var(--duration) var(--ease),
    box-shadow var(--duration) var(--ease);
}
.search__input::-webkit-search-cancel-button {
  display: none;
}
.search__input::placeholder {
  color: var(--text-subtle);
}
.search__input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px var(--focus-ring);
}
.search__clear {
  position: absolute;
  right: 8px;
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 50%;
  background: var(--surface-sunken);
  color: var(--text-muted);
}
</style>
