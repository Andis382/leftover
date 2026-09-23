<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { PhPaperPlaneTilt, PhPhone, PhWhatsappLogo } from '@phosphor-icons/vue'
import UiDialog from '@/components/ui/UiDialog.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiField from '@/components/ui/UiField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import UiNotice from '@/components/ui/UiNotice.vue'
import { api, ApiError } from '@/lib/api'
import { formatPhone } from '@/lib/format'
import { waLink } from '@/lib/whatsapp'
import { useToasts } from '@/stores/toasts'
import type { Plan, PlanMessagePreview } from '@/types'

/** Shows the exact WhatsApp text before it goes out; sends it, or opens it in WhatsApp. */
const open = defineModel<boolean>('open', { default: false })
const props = defineProps<{ date: string; time: string }>()
const emit = defineEmits<{ sent: [plan: Plan] }>()

const { t } = useI18n()
const toasts = useToasts()
const preview = ref<PlanMessagePreview | null>(null)
const phone = ref('')
const error = ref<string | null>(null)
const sending = ref(false)

const whatsappUrl = computed(() =>
  preview.value ? waLink(phone.value || preview.value.phone, preview.value.body) : null,
)

watch(open, async (isOpen) => {
  if (!isOpen) return
  preview.value = null
  error.value = null
  try {
    preview.value = await api.get<PlanMessagePreview>(`/plans/${props.date}/message`)
    phone.value = preview.value.phone ? formatPhone(preview.value.phone) : ''
  } catch {
    error.value = t('errors.generic')
  }
})

async function send() {
  sending.value = true
  error.value = null
  try {
    const result = await api.post<{ plan: Plan }>(`/plans/${props.date}/send`, {
      phone: phone.value || null,
    })
    emit('sent', result.plan)
    toasts.success(t('plan.sentToast', { phone: phone.value }))
    open.value = false
  } catch (e) {
    error.value = e instanceof ApiError ? (e.errors.phone?.[0] ?? e.message) : t('errors.generic')
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <UiDialog v-model:open="open" :title="$t('plan.sendTitle')" :description="$t('plan.sendHint')">
    <UiSkeleton v-if="!preview && !error" :lines="5" />
    <div v-else class="stack">
      <UiNotice v-if="error" tone="danger">{{ error }}</UiNotice>
      <UiField id="f-plan-phone" :label="$t('plan.sendTo')" :error="null">
        <template #default="{ id }">
          <UiInput
            :id="id"
            v-model="phone"
            type="tel"
            inputmode="tel"
            autocomplete="tel"
            :icon="PhPhone"
          />
        </template>
      </UiField>
      <div v-if="preview" class="chat" :aria-label="$t('plan.sendPreview')">
        <div class="bubble">
          <p class="bubble__text">{{ preview.body }}</p>
          <span class="bubble__meta">{{ time }}</span>
        </div>
      </div>
    </div>
    <template #footer>
      <UiButton
        v-if="whatsappUrl"
        variant="secondary"
        :icon="PhWhatsappLogo"
        :href="whatsappUrl"
        target="_blank"
        >{{ $t('plan.openWhatsApp') }}</UiButton
      >
      <UiButton :icon="PhPaperPlaneTilt" :loading="sending" :disabled="!preview" @click="send">{{
        $t('plan.sendNow')
      }}</UiButton>
    </template>
  </UiDialog>
</template>

<style scoped>
.chat {
  padding: 16px;
  border-radius: var(--radius);
  background:
    radial-gradient(circle at 20% 20%, rgb(255 255 255 / 0.5), transparent 40%), var(--chat-bg);
  box-shadow: inset 0 1px 3px rgb(60 30 10 / 0.12);
}
.bubble {
  position: relative;
  max-width: 94%;
  margin-left: auto;
  padding: 10px 12px 18px;
  border-radius: 12px 12px 4px 12px;
  background: var(--chat-bubble);
  box-shadow: 0 1px 1px rgb(0 0 0 / 0.12);
}
.bubble__text {
  white-space: pre-wrap;
  font-size: var(--text-sm);
  line-height: 1.45;
  color: var(--chat-text);
}
.bubble__meta {
  position: absolute;
  right: 10px;
  bottom: 4px;
  font-size: 10px;
  color: var(--chat-meta);
}
</style>
