<script setup lang="ts">
import { watchEffect } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import UiToaster from '@/components/ui/UiToaster.vue'
import UiConfirmHost from '@/components/ui/UiConfirmHost.vue'

const { t } = useI18n()
const route = useRoute()

watchEffect(() => {
  const page = route.meta.title ? t(route.meta.title) : null
  document.title = page ? `${page} · ${t('app.name')}` : t('app.name')
})
</script>

<template>
  <RouterView />
  <UiToaster />
  <UiConfirmHost />
</template>
