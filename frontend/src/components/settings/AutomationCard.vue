<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { PhBellRinging, PhLightning, PhRobot, PhSunHorizon } from '@phosphor-icons/vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import { api } from '@/lib/api'
import { formatPhone, formatTime } from '@/lib/format'
import { useToasts } from '@/stores/toasts'
import type { Automation } from '@/types'

/** The two daily WhatsApp jobs: what they will do today, what they did, and "run now" in the demo. */
const { t } = useI18n()
const toasts = useToasts()
const status = ref<Automation | null>(null)
const running = ref<'plan' | 'reminder' | null>(null)

async function load() {
  status.value = await api.get<Automation>('/automation')
}

async function run(job: 'plan' | 'reminder') {
  running.value = job
  try {
    status.value = await api.post<Automation>(
      job === 'plan' ? '/automation/morning-plan' : '/automation/count-reminder',
    )
    const outcome = status.value.outcome ?? 'sent'
    if (outcome === 'sent') toasts.success(t(`settings.automation.outcome.${outcome}`))
    else toasts.info(t(`settings.automation.outcome.${outcome}`))
  } catch {
    toasts.error(t('errors.generic'))
  } finally {
    running.value = null
  }
}

defineExpose({ load })
onMounted(load)
</script>

<template>
  <UiCard
    :title="$t('settings.automation.title')"
    :subtitle="$t('settings.automation.hint')"
    :icon="PhRobot"
  >
    <UiSkeleton v-if="!status" :lines="4" />
    <div v-else class="jobs">
      <article class="job">
        <span class="job__icon"
          ><PhSunHorizon :size="22" weight="duotone" aria-hidden="true"
        /></span>
        <div class="job__text">
          <h3>{{ $t('settings.automation.planJob') }}</h3>
          <p class="small muted">
            {{
              $t('settings.automation.planWhen', {
                time: status.planTime,
                phone: status.planRecipient ? formatPhone(status.planRecipient) : '—',
              })
            }}
          </p>
          <div class="job__state">
            <UiBadge v-if="!status.openToday" size="sm">{{
              $t('settings.automation.closedToday')
            }}</UiBadge>
            <UiBadge v-else-if="status.planSentAt" tone="success" size="sm" dot>{{
              $t('settings.automation.sentAt', { time: formatTime(status.planSentAt) })
            }}</UiBadge>
            <UiBadge v-else tone="info" size="sm" dot>{{
              $t('settings.automation.dueAt', { time: formatTime(status.planAt) })
            }}</UiBadge>
          </div>
        </div>
        <UiButton
          v-if="status.demo"
          variant="secondary"
          size="sm"
          :icon="PhLightning"
          :loading="running === 'plan'"
          @click="run('plan')"
          >{{ $t('settings.automation.runNow') }}</UiButton
        >
      </article>

      <article class="job">
        <span class="job__icon"
          ><PhBellRinging :size="22" weight="duotone" aria-hidden="true"
        /></span>
        <div class="job__text">
          <h3>{{ $t('settings.automation.reminderJob') }}</h3>
          <p class="small muted">
            {{
              $t('settings.automation.reminderWhen', {
                names: status.reminderRecipients.map((r) => r.name).join(', ') || '—',
              })
            }}
          </p>
          <div class="job__state">
            <UiBadge v-if="!status.openToday" size="sm">{{
              $t('settings.automation.closedToday')
            }}</UiBadge>
            <UiBadge v-else-if="status.countStatus !== 'OPEN'" tone="success" size="sm" dot>{{
              $t('settings.automation.notNeeded')
            }}</UiBadge>
            <UiBadge v-else-if="status.reminderSentAt" tone="accent" size="sm" dot>{{
              $t('settings.automation.sentAt', { time: formatTime(status.reminderSentAt) })
            }}</UiBadge>
            <UiBadge v-else tone="info" size="sm" dot>{{
              $t('settings.automation.dueAt', { time: formatTime(status.reminderAt) })
            }}</UiBadge>
          </div>
        </div>
        <UiButton
          v-if="status.demo"
          variant="secondary"
          size="sm"
          :icon="PhLightning"
          :loading="running === 'reminder'"
          @click="run('reminder')"
          >{{ $t('settings.automation.runNow') }}</UiButton
        >
      </article>
      <p v-if="status.demo" class="xsmall subtle">{{ $t('settings.automation.demoHint') }}</p>
    </div>
  </UiCard>
</template>

<style scoped>
.jobs {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.job {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-muted);
}
.job__icon {
  display: grid;
  place-items: center;
  flex: none;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  color: var(--accent-soft-text);
  background: var(--accent-soft);
}
.job__text {
  flex: 1;
  min-width: 0;
}
.job__text h3 {
  font-size: var(--text-md);
}
.job__state {
  margin-top: 6px;
}
@media (max-width: 560px) {
  .job {
    flex-wrap: wrap;
  }
}
</style>
