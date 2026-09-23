<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import UiDialog from '@/components/ui/UiDialog.vue'
import UiChip from '@/components/ui/UiChip.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiField from '@/components/ui/UiField.vue'
import UiInput from '@/components/ui/UiInput.vue'

/** Skipping is explicit and has a reason, so the forecast knows the gap is a gap, not a zero. */
const open = defineModel<boolean>('open', { default: false })
defineProps<{ busy?: boolean }>()
const emit = defineEmits<{ skip: [reason: string] }>()

const { t } = useI18n()
const presets = ['closedEarly', 'forgot', 'nobody'] as const
const reason = ref('')

watch(open, (isOpen) => {
  if (isOpen) reason.value = ''
})

const chosen = computed(
  () => presets.find((p) => t(`count.skipReasons.${p}`) === reason.value) ?? null,
)
</script>

<template>
  <UiDialog
    v-model:open="open"
    size="sm"
    :title="$t('count.skipTitle')"
    :description="$t('count.skipText')"
  >
    <form
      id="skip-form"
      class="stack"
      novalidate
      @submit.prevent="reason.trim() && emit('skip', reason.trim())"
    >
      <div class="reasons" role="group" :aria-label="$t('count.skipReason')">
        <UiChip
          v-for="p in presets"
          :key="p"
          :pressed="chosen === p"
          @click="reason = $t(`count.skipReasons.${p}`)"
        >
          {{ $t(`count.skipReasons.${p}`) }}
        </UiChip>
      </div>
      <UiField id="f-skip-reason" :label="$t('count.skipReason')">
        <template #default="{ id }">
          <UiInput :id="id" v-model="reason" maxlength="200" />
        </template>
      </UiField>
    </form>
    <template #footer>
      <UiButton variant="ghost" @click="open = false">{{ $t('common.cancel') }}</UiButton>
      <UiButton type="submit" form="skip-form" :disabled="!reason.trim()" :loading="busy">{{
        $t('count.skipConfirm')
      }}</UiButton>
    </template>
  </UiDialog>
</template>

<style scoped>
.reasons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
