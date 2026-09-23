<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  PhArchive,
  PhArrowCounterClockwise,
  PhArrowDown,
  PhArrowUp,
  PhBasket,
  PhPencilSimple,
  PhPlus,
} from '@phosphor-icons/vue'
import AppPage from '@/components/layout/AppPage.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import UiIconButton from '@/components/ui/UiIconButton.vue'
import UiMenu from '@/components/ui/UiMenu.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import ProductDialog from '@/components/products/ProductDialog.vue'
import { api } from '@/lib/api'
import { CATEGORY_ICONS } from '@/lib/categories'
import { weekdayName } from '@/lib/dates'
import { formatMoney } from '@/lib/format'
import { useConfirm } from '@/stores/confirm'
import { useToasts } from '@/stores/toasts'
import type { Product } from '@/types'

const { t } = useI18n()
const toasts = useToasts()
const confirm = useConfirm()

const products = ref<Product[]>([])
const loading = ref(true)
const moving = ref<number | null>(null)
const dialogOpen = ref(false)
const editing = ref<Product | null>(null)

const shelf = computed(() => products.value.filter((p) => p.active))
const archived = computed(() => products.value.filter((p) => !p.active))

async function load() {
  loading.value = true
  try {
    products.value = await api.get<Product[]>('/products')
  } catch {
    toasts.error(t('errors.generic'))
  } finally {
    loading.value = false
  }
}

function add() {
  editing.value = null
  dialogOpen.value = true
}

function edit(product: Product) {
  editing.value = product
  dialogOpen.value = true
}

function onSaved(product: Product, created: boolean) {
  const index = products.value.findIndex((p) => p.id === product.id)
  if (index >= 0) products.value[index] = product
  else products.value.push(product)
  toasts.success(created ? t('products.created', { name: product.name }) : t('products.saved'))
}

async function move(product: Product, direction: 'up' | 'down') {
  moving.value = product.id
  try {
    products.value = await api.post<Product[]>(`/products/${product.id}/move`, { direction })
  } catch {
    toasts.error(t('errors.generic'))
  } finally {
    moving.value = null
  }
}

async function archive(product: Product) {
  const ok = await confirm.ask({
    title: t('products.archiveTitle', { name: product.name }),
    text: t('products.archiveText'),
    confirmLabel: t('products.archive'),
    danger: true,
  })
  if (!ok) return
  await api.post(`/products/${product.id}/archive`)
  toasts.success(t('products.archived', { name: product.name }))
  await load()
}

async function restore(product: Product) {
  await api.post(`/products/${product.id}/restore`)
  toasts.success(t('products.restored', { name: product.name }))
  await load()
}

onMounted(load)
</script>

<template>
  <AppPage :title="$t('products.title')" :subtitle="$t('products.subtitle', { n: shelf.length })">
    <template #actions>
      <UiButton :icon="PhPlus" @click="add">{{ $t('products.add') }}</UiButton>
    </template>

    <UiSkeleton v-if="loading && !products.length" card :lines="8" />

    <UiEmpty
      v-else-if="!shelf.length && !archived.length"
      :icon="PhBasket"
      :title="$t('products.emptyTitle')"
      :text="$t('products.emptyText')"
    >
      <UiButton :icon="PhPlus" @click="add">{{ $t('products.add') }}</UiButton>
    </UiEmpty>

    <template v-else>
      <UiCard padding="none" :title="$t('products.shelf')" :subtitle="$t('products.shelfHint')">
        <ol class="shelf">
          <li v-for="(p, i) in shelf" :key="p.id" class="item">
            <div class="item__order">
              <UiIconButton
                :icon="PhArrowUp"
                :label="$t('products.moveUp', { name: p.name })"
                size="sm"
                :disabled="i === 0 || moving !== null"
                @click="move(p, 'up')"
              />
              <UiIconButton
                :icon="PhArrowDown"
                :label="$t('products.moveDown', { name: p.name })"
                size="sm"
                :disabled="i === shelf.length - 1 || moving !== null"
                @click="move(p, 'down')"
              />
            </div>
            <div class="item__main">
              <p class="item__name">
                <span class="item__icon"
                  ><component
                    :is="CATEGORY_ICONS[p.category]"
                    :size="16"
                    weight="duotone"
                    aria-hidden="true"
                /></span>
                {{ p.name }}
              </p>
              <p class="item__meta">
                <UiBadge size="sm">{{ $t(`categories.${p.category}`) }}</UiBadge>
                <span class="num">{{ formatMoney(p.unitPriceCents, { decimals: true }) }}</span>
                <span v-if="p.unitCostCents !== null" class="subtle num">{{
                  $t('products.costShort', {
                    cost: formatMoney(p.unitCostCents, { decimals: true }),
                  })
                }}</span>
                <span class="subtle">{{ $t('products.trayShort', { n: p.traySize }) }}</span>
              </p>
            </div>
            <ol class="days" :aria-label="$t('products.fields.baselines')">
              <li
                v-for="day in 7"
                :key="day"
                class="day"
                :class="{ 'is-off': !p.activeWeekdays.includes(day) }"
              >
                <span class="day__name">{{ weekdayName(day, 'short') }}</span>
                <span class="day__qty num">{{
                  p.activeWeekdays.includes(day) ? p.baselines[day - 1] : '—'
                }}</span>
              </li>
            </ol>
            <div class="item__actions">
              <UiIconButton
                variant="secondary"
                :icon="PhPencilSimple"
                :label="$t('products.editName', { name: p.name })"
                @click="edit(p)"
              />
              <UiMenu
                :label="$t('common.more')"
                :items="[
                  {
                    label: $t('products.archive'),
                    icon: PhArchive,
                    danger: true,
                    action: () => archive(p),
                  },
                ]"
              />
            </div>
          </li>
        </ol>
      </UiCard>

      <UiCard
        v-if="archived.length"
        :title="$t('products.archivedTitle')"
        :subtitle="$t('products.archivedHint')"
        :icon="PhArchive"
        tone="muted"
      >
        <ul class="archived">
          <li v-for="p in archived" :key="p.id">
            <span class="strong">{{ p.name }}</span>
            <UiButton
              variant="ghost"
              size="sm"
              :icon="PhArrowCounterClockwise"
              @click="restore(p)"
              >{{ $t('products.restore') }}</UiButton
            >
          </li>
        </ul>
      </UiCard>
    </template>

    <ProductDialog v-model:open="dialogOpen" :product="editing" @saved="onSaved" />
  </AppPage>
</template>

<style scoped>
.shelf {
  margin: 0;
  padding: 0;
  list-style: none;
}
.item {
  display: grid;
  grid-template-columns: auto minmax(0, 1.2fr) minmax(0, 1.6fr) auto;
  align-items: center;
  gap: 12px 18px;
  padding: 12px 18px 12px 12px;
  border-top: 1px solid var(--border);
}
.item:hover {
  background: var(--surface-hover);
}
.item__order {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.item__name {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
}
.item__icon {
  display: grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: 8px;
  color: var(--primary);
  background: var(--primary-soft);
}
.item__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 12px;
  margin-top: 4px;
  font-size: var(--text-sm);
}
.days {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 4px;
  margin: 0;
  padding: 0;
  list-style: none;
}
.day {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 5px 2px;
  border-radius: 8px;
  background: var(--surface-muted);
  border: 1px solid var(--border);
}
.day__name {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-subtle);
}
.day__qty {
  font-weight: 700;
  font-size: var(--text-sm);
}
.day.is-off {
  background: transparent;
  border-style: dashed;
}
.day.is-off .day__qty {
  color: var(--gray-300);
}
.item__actions {
  display: flex;
  align-items: center;
  gap: 6px;
}
.archived {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin: 0;
  padding: 0;
  list-style: none;
}
.archived li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
@media (max-width: 900px) {
  .item {
    grid-template-columns: auto minmax(0, 1fr) auto;
    grid-template-areas:
      'order main actions'
      'order days days';
    gap: 10px 12px;
    padding: 12px 12px 14px 8px;
  }
  .item__actions {
    align-self: start;
  }
  .item__order {
    grid-area: order;
  }
  .item__main {
    grid-area: main;
  }
  .days {
    grid-area: days;
  }
  .item__actions {
    grid-area: actions;
  }
}
</style>
