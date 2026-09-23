<script setup lang="ts">
import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import UiDialog from '@/components/ui/UiDialog.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiChip from '@/components/ui/UiChip.vue'
import UiField from '@/components/ui/UiField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiStepper from '@/components/ui/UiStepper.vue'
import UiFormErrors from '@/components/ui/UiFormErrors.vue'
import { api } from '@/lib/api'
import { weekdayName } from '@/lib/dates'
import { centsToInput, parseMoney } from '@/lib/format'
import { useForm } from '@/lib/form'
import { CATEGORIES, type Category, type Product } from '@/types'

/** Add or edit a product: price, cost, tray size and the usual number for each weekday. */
const open = defineModel<boolean>('open', { default: false })
const props = defineProps<{ product: Product | null }>()
const emit = defineEmits<{ saved: [product: Product, created: boolean] }>()

const { t } = useI18n()
const form = useForm({
  name: '',
  category: 'BREAD' as Category,
  price: '',
  cost: '',
  traySize: 1 as number | null,
  baselines: [0, 0, 0, 0, 0, 0, 0] as number[],
  activeWeekdays: [1, 2, 3, 4, 5, 6, 7] as number[],
})

const categories = computed(() =>
  CATEGORIES.map((c) => ({ value: c, label: t(`categories.${c}`) })),
)
const baselineError = computed(
  () =>
    Object.entries(form.errors.value).find(
      ([k]) => k.startsWith('baselines') || k.startsWith('activeWeekdays'),
    )?.[1]?.[0],
)

watch(open, (isOpen) => {
  if (!isOpen) return
  const p = props.product
  form.reset(
    p
      ? {
          name: p.name,
          category: p.category,
          price: centsToInput(p.unitPriceCents),
          cost: centsToInput(p.unitCostCents),
          traySize: p.traySize,
          baselines: [...p.baselines],
          activeWeekdays: [...p.activeWeekdays],
        }
      : { baselines: [0, 0, 0, 0, 0, 0, 0], activeWeekdays: [1, 2, 3, 4, 5, 6, 7] },
  )
})

function toggleDay(day: number) {
  const days = form.data.activeWeekdays
  form.data.activeWeekdays = days.includes(day)
    ? days.filter((d) => d !== day)
    : [...days, day].sort()
}

async function save() {
  const payload = {
    name: form.data.name,
    category: form.data.category,
    unitPriceCents: parseMoney(form.data.price),
    unitCostCents: parseMoney(form.data.cost),
    traySize: form.data.traySize ?? 1,
    baselines: form.data.baselines.map((n) => Number(n) || 0),
    activeWeekdays: form.data.activeWeekdays,
  }
  const created = !props.product
  const saved = await form.submit(() =>
    props.product
      ? api.put<Product>(`/products/${props.product.id}`, payload)
      : api.post<Product>('/products', payload),
  )
  if (saved) {
    emit('saved', saved, created)
    open.value = false
  }
}
</script>

<template>
  <UiDialog
    v-model:open="open"
    size="lg"
    :title="product ? $t('products.editTitle') : $t('products.addTitle')"
    :description="$t('products.dialogHint')"
  >
    <form id="product-form" class="stack" novalidate @submit.prevent="save">
      <UiFormErrors
        :errors="form.errors.value"
        :message="form.message.value"
        :trigger="form.submitted.value"
        :ids="{ unitPriceCents: 'f-price', unitCostCents: 'f-cost', traySize: 'f-tray' }"
      />
      <div class="grid-2">
        <UiField
          id="f-name"
          :label="$t('products.fields.name')"
          :error="form.error('name')"
          required
        >
          <template #default="{ id, invalid, describedby }">
            <UiInput
              :id="id"
              v-model="form.data.name"
              :invalid="invalid"
              :describedby="describedby"
              maxlength="120"
            />
          </template>
        </UiField>
        <UiField
          id="f-category"
          :label="$t('products.fields.category')"
          :error="form.error('category')"
        >
          <template #default="{ id }">
            <UiSelect :id="id" v-model="form.data.category" :options="categories" />
          </template>
        </UiField>
      </div>
      <div class="grid-3">
        <UiField
          id="f-price"
          :label="$t('products.fields.price')"
          :error="form.error('unitPriceCents')"
          required
        >
          <template #default="{ id, invalid, describedby }">
            <UiInput
              :id="id"
              v-model="form.data.price"
              prefix="€"
              inputmode="decimal"
              :invalid="invalid"
              :describedby="describedby"
            />
          </template>
        </UiField>
        <UiField
          id="f-cost"
          :label="$t('products.fields.cost')"
          :hint="$t('products.fields.costHint')"
          :error="form.error('unitCostCents')"
          optional
        >
          <template #default="{ id, invalid, describedby }">
            <UiInput
              :id="id"
              v-model="form.data.cost"
              prefix="€"
              inputmode="decimal"
              :invalid="invalid"
              :describedby="describedby"
            />
          </template>
        </UiField>
        <UiField
          id="f-tray"
          :label="$t('products.fields.tray')"
          :hint="$t('products.fields.trayHint')"
          :error="form.error('traySize')"
        >
          <template #default="{ id }">
            <UiStepper
              :id="id"
              v-model="form.data.traySize"
              :min="1"
              :max="500"
              :label="$t('products.fields.tray')"
            />
          </template>
        </UiField>
      </div>

      <fieldset class="week">
        <legend class="week__legend">{{ $t('products.fields.baselines') }}</legend>
        <p class="week__hint">{{ $t('products.fields.baselinesHint') }}</p>
        <div class="week__grid">
          <div
            v-for="day in 7"
            :key="day"
            class="week__day"
            :class="{ 'is-off': !form.data.activeWeekdays.includes(day) }"
          >
            <UiChip
              size="md"
              tone="primary"
              :pressed="form.data.activeWeekdays.includes(day)"
              @click="toggleDay(day)"
            >
              {{ weekdayName(day, 'short') }}
            </UiChip>
            <UiInput
              :id="`f-baseline-${day}`"
              v-model.number="form.data.baselines[day - 1]"
              type="number"
              inputmode="numeric"
              min="0"
              :disabled="!form.data.activeWeekdays.includes(day)"
              :aria-label="$t('products.fields.baselineFor', { day: weekdayName(day) })"
            />
          </div>
        </div>
        <p v-if="baselineError" class="week__error" role="alert">{{ baselineError }}</p>
      </fieldset>
    </form>
    <template #footer>
      <UiButton variant="ghost" @click="open = false">{{ $t('common.cancel') }}</UiButton>
      <UiButton type="submit" form="product-form" :loading="form.processing.value">{{
        $t('common.save')
      }}</UiButton>
    </template>
  </UiDialog>
</template>

<style scoped>
.week {
  margin: 0;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-muted);
}
.week__legend {
  padding: 0 6px;
  font-size: var(--text-sm);
  font-weight: 650;
}
.week__hint {
  margin-bottom: 12px;
  font-size: var(--text-xs);
  color: var(--text-subtle);
}
.week__grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 8px;
}
.week__day {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.week__day :deep(.chip) {
  justify-content: center;
  padding: 0 4px;
}
.week__day :deep(.control__input) {
  text-align: center;
  font-variant-numeric: tabular-nums;
  padding-inline: 4px;
}
.week__day.is-off :deep(.control) {
  opacity: 0.45;
}
.week__error {
  margin-top: 8px;
  font-size: var(--text-sm);
  color: var(--danger-text);
}
@media (max-width: 560px) {
  .week__grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}
</style>
