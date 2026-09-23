<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { PhCalendarBlank, PhCaretLeft, PhCaretRight } from '@phosphor-icons/vue'
import UiIconButton from '@/components/ui/UiIconButton.vue'
import { addDays, isDay } from '@/lib/dates'
import { formatDate } from '@/lib/format'

/** Day switcher for the dark band: previous, the date (opens the picker), next, today/tomorrow. */
const props = defineProps<{ date: string; today: string; route: string }>()
const router = useRouter()
const picker = ref<HTMLInputElement | null>(null)

const tomorrow = computed(() => addDays(props.today, 1))

function go(day: string) {
  if (isDay(day)) router.push({ name: props.route, params: { date: day } })
}

function openPicker() {
  const input = picker.value
  if (!input) return
  if (typeof input.showPicker === 'function') input.showPicker()
  else input.focus()
}
</script>

<template>
  <div class="pager">
    <div class="pager__main">
      <UiIconButton
        :icon="PhCaretLeft"
        :label="$t('plan.prevDay')"
        variant="inverse"
        size="sm"
        @click="go(addDays(date, -1))"
      />
      <button type="button" class="pager__date" @click="openPicker">
        <PhCalendarBlank :size="16" weight="bold" aria-hidden="true" />
        <span>{{ formatDate(date, 'medium') }}</span>
      </button>
      <input
        ref="picker"
        class="visually-hidden"
        type="date"
        :value="date"
        :aria-label="$t('plan.pickDate')"
        tabindex="-1"
        @change="go(($event.target as HTMLInputElement).value)"
      />
      <UiIconButton
        :icon="PhCaretRight"
        :label="$t('plan.nextDay')"
        variant="inverse"
        size="sm"
        @click="go(addDays(date, 1))"
      />
    </div>
    <div class="pager__quick">
      <button
        type="button"
        class="pager__pill"
        :class="{ 'is-active': date === today }"
        :aria-pressed="date === today"
        @click="go(today)"
      >
        {{ $t('common.today') }}
      </button>
      <button
        type="button"
        class="pager__pill"
        :class="{ 'is-active': date === tomorrow }"
        :aria-pressed="date === tomorrow"
        @click="go(tomorrow)"
      >
        {{ $t('common.tomorrow') }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.pager {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}
.pager__main {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px;
  border: 1px solid rgb(255 255 255 / 0.16);
  border-radius: var(--radius-pill);
  background: rgb(255 255 255 / 0.07);
}
.pager__date {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 36px;
  padding: 0 12px;
  border: 0;
  border-radius: var(--radius-pill);
  background: transparent;
  color: var(--header-text);
  font-weight: 650;
  font-size: var(--text-sm);
  font-variant-numeric: tabular-nums;
}
.pager__date:hover {
  background: rgb(255 255 255 / 0.1);
}
.pager__date:focus-visible,
.pager__pill:focus-visible {
  outline: 3px solid rgb(255 255 255 / 0.5);
  outline-offset: 2px;
}
.pager__quick {
  display: inline-flex;
  gap: 4px;
}
.pager__pill {
  min-height: 36px;
  padding: 0 14px;
  border: 1px solid rgb(255 255 255 / 0.16);
  border-radius: var(--radius-pill);
  background: transparent;
  color: var(--text-inverse-muted);
  font-size: var(--text-sm);
  font-weight: 650;
}
.pager__pill:hover {
  color: var(--header-text);
  background: rgb(255 255 255 / 0.08);
}
.pager__pill.is-active {
  color: var(--header-from);
  background: var(--accent-400);
  border-color: var(--accent-400);
}
</style>
