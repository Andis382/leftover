<script setup lang="ts">
/** Label/value pairs for detail screens: <UiDl :items="[{ label: 'Serial', value: 'X1' }]" /> */
defineProps<{
  items: { label: string; value: string | number | null | undefined; mono?: boolean }[]
  columns?: 1 | 2
}>()
</script>

<template>
  <dl class="dl" :class="{ 'dl--2': columns === 2 }">
    <div v-for="item in items" :key="item.label" class="dl__row">
      <dt>{{ item.label }}</dt>
      <dd :class="{ mono: item.mono }">
        {{
          item.value === null || item.value === undefined || item.value === '' ? '—' : item.value
        }}
      </dd>
    </div>
  </dl>
</template>

<style scoped>
.dl {
  display: grid;
  gap: 0;
  margin: 0;
}
.dl--2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  column-gap: 24px;
}
@media (max-width: 640px) {
  .dl--2 {
    grid-template-columns: minmax(0, 1fr);
  }
}
.dl__row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 16px;
  padding: 11px 0;
  border-bottom: 1px solid var(--border);
}
dt {
  font-size: var(--text-sm);
  color: var(--text-muted);
}
dd {
  margin: 0;
  font-size: var(--text-sm);
  font-weight: 650;
  text-align: right;
  overflow-wrap: anywhere;
}
</style>
