<script setup lang="ts">
import { PhCompass } from '@phosphor-icons/vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import UiButton from '@/components/ui/UiButton.vue'
</script>

<template>
  <div class="nf">
    <UiEmpty
      :icon="PhCompass"
      :title="$t('errors.notFoundTitle')"
      :text="$t('errors.notFoundText')"
    >
      <UiButton to="/">{{ $t('errors.goHome') }}</UiButton>
    </UiEmpty>
  </div>
</template>

<style scoped>
.nf {
  min-height: 100dvh;
  display: grid;
  place-items: center;
}
</style>
