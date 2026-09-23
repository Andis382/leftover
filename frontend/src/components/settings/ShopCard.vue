<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { PhClock, PhPhone, PhStorefront } from '@phosphor-icons/vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiField from '@/components/ui/UiField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiStepper from '@/components/ui/UiStepper.vue'
import UiSwitch from '@/components/ui/UiSwitch.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import UiFormErrors from '@/components/ui/UiFormErrors.vue'
import { api } from '@/lib/api'
import { weekdayName } from '@/lib/dates'
import { formatPhone } from '@/lib/format'
import { useForm } from '@/lib/form'
import { useToasts } from '@/stores/toasts'
import type { ShopHours, ShopSettings } from '@/types'

/** Opening hours per weekday, when the plan goes out, and when the count reminder does. */
const emit = defineEmits<{ saved: [] }>()
const { t } = useI18n()
const toasts = useToasts()
const loaded = ref(false)
const form = useForm({
  hours: [] as ShopHours[],
  planTime: '04:00',
  countReminderOffset: 15 as number | null,
  planPhone: '',
})

/** Error summary names for the time inputs, which have no visible label of their own. */
const hourLabels = computed(() =>
  Object.fromEntries(
    form.data.hours.flatMap((h, i) => [
      [`hours.${i}.opensAt`, t('settings.shop.opensOn', { day: weekdayName(h.weekday) })],
      [`hours.${i}.closesAt`, t('settings.shop.closesOn', { day: weekdayName(h.weekday) })],
    ]),
  ),
)

function apply(settings: ShopSettings) {
  form.reset({
    hours: settings.hours.map((h) => ({ ...h })),
    planTime: settings.planTime,
    countReminderOffset: settings.countReminderOffset,
    planPhone: settings.planPhone ? formatPhone(settings.planPhone) : '',
  })
}

function toggle(day: ShopHours, open: boolean) {
  day.open = open
  if (open) {
    day.opensAt ??= '07:00'
    day.closesAt ??= '19:00'
  }
}

async function save() {
  const saved = await form.submit(() =>
    api.put<ShopSettings>('/shop', {
      hours: form.data.hours,
      planTime: form.data.planTime,
      countReminderOffset: form.data.countReminderOffset ?? 0,
      planPhone: form.data.planPhone || null,
    }),
  )
  if (saved) {
    apply(saved)
    toasts.success(t('settings.saved'))
    emit('saved')
  }
}

onMounted(async () => {
  apply(await api.get<ShopSettings>('/shop'))
  loaded.value = true
})
</script>

<template>
  <UiCard
    :title="$t('settings.shop.title')"
    :subtitle="$t('settings.shop.hint')"
    :icon="PhStorefront"
  >
    <UiSkeleton v-if="!loaded" :lines="7" />
    <form v-else class="stack" novalidate @submit.prevent="save">
      <UiFormErrors
        :errors="form.errors.value"
        :message="form.message.value"
        :trigger="form.submitted.value"
        :ids="{
          planTime: 'f-plan-time',
          countReminderOffset: 'f-offset',
          planPhone: 'f-plan-phone-setting',
        }"
        :labels="hourLabels"
      />
      <div class="hours">
        <div
          v-for="(day, i) in form.data.hours"
          :key="day.weekday"
          class="hours__row"
          :class="{ 'is-closed': !day.open }"
        >
          <span class="hours__day">{{ weekdayName(day.weekday) }}</span>
          <UiSwitch
            :id="`f-open-${day.weekday}`"
            :model-value="day.open"
            :label="day.open ? $t('settings.shop.open') : $t('settings.shop.closed')"
            @update:model-value="(v) => toggle(day, v)"
          />
          <div class="hours__times">
            <UiInput
              :id="`f-hours.${i}.opensAt`"
              v-model="day.opensAt"
              type="time"
              :disabled="!day.open"
              :invalid="!!form.error(`hours.${i}.opensAt`)"
              :aria-label="$t('settings.shop.opensOn', { day: weekdayName(day.weekday) })"
            />
            <span class="hours__dash" aria-hidden="true">–</span>
            <UiInput
              :id="`f-hours.${i}.closesAt`"
              v-model="day.closesAt"
              type="time"
              :disabled="!day.open"
              :invalid="!!form.error(`hours.${i}.closesAt`)"
              :aria-label="$t('settings.shop.closesOn', { day: weekdayName(day.weekday) })"
            />
          </div>
        </div>
      </div>

      <div class="grid-3">
        <UiField
          id="f-plan-time"
          :label="$t('settings.shop.planTime')"
          :hint="$t('settings.shop.planTimeHint')"
          :error="form.error('planTime')"
        >
          <template #default="{ id, invalid, describedby }">
            <UiInput
              :id="id"
              v-model="form.data.planTime"
              type="time"
              :icon="PhClock"
              :invalid="invalid"
              :describedby="describedby"
            />
          </template>
        </UiField>
        <UiField
          id="f-offset"
          :label="$t('settings.shop.reminder')"
          :hint="$t('settings.shop.reminderHint')"
          :error="form.error('countReminderOffset')"
        >
          <template #default="{ id }">
            <UiStepper
              :id="id"
              v-model="form.data.countReminderOffset"
              :min="0"
              :max="240"
              :step="5"
              :label="$t('settings.shop.reminder')"
            />
          </template>
        </UiField>
        <UiField
          id="f-plan-phone-setting"
          :label="$t('settings.shop.planPhone')"
          :hint="$t('settings.shop.planPhoneHint')"
          :error="form.error('planPhone')"
        >
          <template #default="{ id, invalid, describedby }">
            <UiInput
              :id="id"
              v-model="form.data.planPhone"
              type="tel"
              inputmode="tel"
              :icon="PhPhone"
              :invalid="invalid"
              :describedby="describedby"
            />
          </template>
        </UiField>
      </div>
      <div>
        <UiButton type="submit" :loading="form.processing.value">{{ $t('common.save') }}</UiButton>
      </div>
    </form>
  </UiCard>
</template>

<style scoped>
.hours {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}
.hours__row {
  display: grid;
  grid-template-columns: 120px 150px minmax(0, 1fr);
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: var(--surface);
}
.hours__row + .hours__row {
  border-top: 1px solid var(--border);
}
.hours__row.is-closed {
  background: var(--surface-muted);
}
.hours__day {
  font-weight: 650;
  text-transform: capitalize;
}
.hours__times {
  display: flex;
  align-items: center;
  gap: 8px;
  max-width: 320px;
}
.hours__dash {
  color: var(--text-subtle);
}
@media (max-width: 640px) {
  .hours__row {
    grid-template-columns: minmax(0, 1fr) auto;
  }
  .hours__times {
    grid-column: 1 / -1;
    max-width: none;
  }
}
</style>
