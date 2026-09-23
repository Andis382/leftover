<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  PhArrowClockwise,
  PhBellRinging,
  PhChatCircleDots,
  PhPaperPlaneTilt,
  PhSunHorizon,
  PhWhatsappLogo,
} from '@phosphor-icons/vue'
import AppPage from '@/components/layout/AppPage.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import UiTabs from '@/components/ui/UiTabs.vue'
import UiNotice from '@/components/ui/UiNotice.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import { api } from '@/lib/api'
import { formatDateTime, formatPhone } from '@/lib/format'

export type OutboundMessage = {
  id: number
  recipient: string
  recipientName: string | null
  templateKey: string
  body: string
  status: 'QUEUED' | 'SENT' | 'DELIVERED' | 'READ' | 'FAILED' | 'SIMULATED'
  error: string | null
  createdAt: string
  waMeUrl: string
}
type InboundMessage = {
  id: number
  fromPhone: string
  body: string | null
  kind: string
  handled: boolean
  handledBy: string | null
  receivedAt: string
}

const { t } = useI18n()
const tab = ref<'out' | 'in'>('out')
const outbox = ref<OutboundMessage[] | null>(null)
const inbox = ref<InboundMessage[] | null>(null)

const tabs = computed(() => [
  {
    value: 'out' as const,
    label: t('messages.outbox'),
    icon: PhPaperPlaneTilt,
    count: outbox.value?.length ?? null,
  },
  {
    value: 'in' as const,
    label: t('messages.inbox'),
    icon: PhChatCircleDots,
    count: inbox.value?.length ?? null,
  },
])
const anySimulated = computed(() => outbox.value?.some((m) => m.status === 'SIMULATED'))

const tone: Record<
  OutboundMessage['status'],
  'neutral' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
> = {
  QUEUED: 'neutral',
  SENT: 'info',
  DELIVERED: 'primary',
  READ: 'success',
  FAILED: 'danger',
  SIMULATED: 'warning',
}
const kinds: Record<string, { icon: typeof PhSunHorizon; key: string }> = {
  bake_plan: { icon: PhSunHorizon, key: 'messages.kinds.bake_plan' },
  count_reminder: { icon: PhBellRinging, key: 'messages.kinds.count_reminder' },
}

async function load() {
  const [o, i] = await Promise.all([
    api.get<OutboundMessage[]>('/messages'),
    api.get<InboundMessage[]>('/messages/inbound'),
  ])
  outbox.value = o
  inbox.value = i
}

onMounted(load)

async function retry(m: OutboundMessage) {
  const updated = await api.post<OutboundMessage>(`/messages/${m.id}/retry`)
  Object.assign(m, updated)
}
</script>

<template>
  <AppPage :title="$t('messages.title')" :subtitle="$t('messages.subtitle')">
    <UiNotice v-if="anySimulated" tone="info">{{ $t('messages.simulatedHint') }}</UiNotice>

    <UiCard padding="none">
      <div class="tabs-wrap">
        <UiTabs v-model="tab" :tabs="tabs" :label="$t('messages.title')" />
      </div>
      <UiSkeleton v-if="!outbox" :lines="5" height="18px" class="pad" />
      <template v-else-if="tab === 'out'">
        <UiEmpty
          v-if="!outbox.length"
          :icon="PhPaperPlaneTilt"
          :title="$t('messages.empty')"
          compact
        />
        <ul v-else class="msgs">
          <li v-for="m in outbox" :key="m.id" class="msg">
            <div class="msg__head">
              <div class="msg__to">
                <UiBadge
                  v-if="kinds[m.templateKey]"
                  tone="accent"
                  size="sm"
                  :icon="kinds[m.templateKey]?.icon"
                  >{{ $t(kinds[m.templateKey]?.key ?? '') }}</UiBadge
                >
                <span class="strong">{{ m.recipientName || formatPhone(m.recipient) }}</span>
                <span v-if="m.recipientName" class="small subtle">{{
                  formatPhone(m.recipient)
                }}</span>
              </div>
              <UiBadge :tone="tone[m.status]" dot size="sm">{{
                $t(`messages.status.${m.status}`)
              }}</UiBadge>
            </div>
            <p class="msg__body">{{ m.body }}</p>
            <p v-if="m.error" class="small msg__error">{{ m.error }}</p>
            <div class="msg__foot">
              <span class="xsmall subtle">{{ formatDateTime(m.createdAt) }}</span>
              <div class="cluster">
                <UiButton
                  v-if="m.status === 'FAILED'"
                  size="sm"
                  variant="ghost"
                  :icon="PhArrowClockwise"
                  @click="retry(m)"
                  >{{ $t('messages.retry') }}</UiButton
                >
                <UiButton
                  size="sm"
                  variant="secondary"
                  :icon="PhWhatsappLogo"
                  :href="m.waMeUrl"
                  target="_blank"
                  >{{ $t('messages.openWhatsApp') }}</UiButton
                >
              </div>
            </div>
          </li>
        </ul>
      </template>
      <template v-else>
        <UiEmpty
          v-if="!inbox?.length"
          :icon="PhChatCircleDots"
          :title="$t('messages.emptyInbox')"
          :text="$t('messages.emptyInboxText')"
          compact
        />
        <ul v-else class="msgs">
          <li v-for="m in inbox" :key="m.id" class="msg">
            <div class="msg__head">
              <span class="strong">{{ formatPhone(m.fromPhone) }}</span>
            </div>
            <p class="msg__body">{{ m.body ?? `[${m.kind}]` }}</p>
            <span class="xsmall subtle">{{ formatDateTime(m.receivedAt) }}</span>
          </li>
        </ul>
      </template>
    </UiCard>
  </AppPage>
</template>

<style scoped>
.tabs-wrap {
  padding: 6px 12px 0;
}
.pad {
  padding: 20px;
}
.msgs {
  margin: 0;
  padding: 0;
  list-style: none;
}
.msg {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
}
.msg:last-child {
  border-bottom: 0;
}
.msg__head,
.msg__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}
.msg__to {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.msg__body {
  max-width: 72ch;
  padding: 12px 14px;
  font-size: var(--text-sm);
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: 4px var(--radius) var(--radius) var(--radius);
}
.msg__error {
  color: var(--danger-text);
}
</style>
