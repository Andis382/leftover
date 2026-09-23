<script setup lang="ts">
import { computed } from 'vue'
import UiDialog from './UiDialog.vue'
import UiButton from './UiButton.vue'
import { useConfirm } from '@/stores/confirm'

const confirm = useConfirm()
const open = computed({
  get: () => confirm.current !== null,
  set: (v) => {
    if (!v) confirm.answer(false)
  },
})
</script>

<template>
  <UiDialog
    v-if="confirm.current"
    v-model:open="open"
    :title="confirm.current.title"
    :description="confirm.current.text"
    size="sm"
  >
    <template #footer>
      <UiButton variant="secondary" @click="confirm.answer(false)">{{
        confirm.current.cancelLabel ?? $t('common.cancel')
      }}</UiButton>
      <UiButton
        :variant="confirm.current.danger ? 'danger' : 'primary'"
        @click="confirm.answer(true)"
      >
        {{ confirm.current.confirmLabel ?? $t('common.confirm') }}
      </UiButton>
    </template>
  </UiDialog>
</template>
