<script setup lang="ts">
import { PhCheckCircle, PhWhatsappLogo } from '@phosphor-icons/vue'
import ChangeChip from '@/components/plan/ChangeChip.vue'

// The sign-in page's left panel: the pitch, and a small piece of the product itself.
const points = ['auth.aside.point1', 'auth.aside.point2', 'auth.aside.point3']
const rows = [
  { name: 'Simite me susam', qty: 312, change: 24, why: 'auth.aside.why1' },
  { name: 'Kroasan me çokollatë', qty: 168, change: 12, why: 'auth.aside.why2' },
  { name: 'Bukë e bardhë', qty: 110, change: -10, why: 'auth.aside.why3' },
]
</script>

<template>
  <div class="aside">
    <h2 class="aside__title">
      {{ $t('auth.aside.title') }}
      <em>{{ $t('auth.aside.titleEm') }}</em>
    </h2>
    <ul class="aside__points">
      <li v-for="p in points" :key="p">
        <PhCheckCircle :size="20" weight="fill" aria-hidden="true" /> {{ $t(p) }}
      </li>
    </ul>

    <div class="artifact" aria-hidden="true">
      <div class="sheet">
        <div class="sheet__head">
          <span class="sheet__title">{{ $t('auth.aside.sheetTitle') }}</span>
          <span class="sheet__day">{{ $t('auth.aside.sheetDay') }}</span>
        </div>
        <div v-for="r in rows" :key="r.name" class="sheet__row">
          <div class="sheet__name">
            <span>{{ r.name }}</span>
            <small>{{ $t(r.why) }}</small>
          </div>
          <ChangeChip :change="r.change" size="sm" />
          <span class="sheet__qty">{{ r.qty }}</span>
        </div>
      </div>
      <div class="bubble">
        <PhWhatsappLogo :size="16" weight="fill" />
        <p>{{ $t('auth.aside.bubble') }}</p>
        <span class="bubble__time">04:00</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.aside__title {
  color: var(--header-text);
  font-size: clamp(1.7rem, 1.1rem + 1.7vw, 2.45rem);
  font-weight: 600;
  font-variation-settings:
    'SOFT' 100,
    'WONK' 0;
  letter-spacing: -0.02em;
  line-height: 1.15;
  max-width: 17ch;
}
.aside__title em {
  display: block;
  margin-top: 6px;
  font-style: italic;
  font-weight: 450;
  color: var(--accent-300);
}
.aside__points {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin: 26px 0 0;
  padding: 0;
  list-style: none;
  color: var(--text-inverse-muted);
  max-width: 44ch;
}
.aside__points li {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}
.aside__points :deep(svg) {
  flex: none;
  margin-top: 2px;
  color: var(--accent-400);
}
.artifact {
  position: relative;
  margin-top: 40px;
  max-width: 400px;
  padding-bottom: 64px;
}
.sheet {
  padding: 16px 18px 10px;
  background: var(--paper);
  color: var(--text);
  border-radius: 6px;
  box-shadow:
    0 1px 0 rgb(255 255 255 / 0.6) inset,
    0 22px 40px -18px rgb(0 0 0 / 0.55),
    0 4px 10px -4px rgb(0 0 0 / 0.35);
  transform: rotate(-1.6deg);
  background-image: repeating-linear-gradient(
    180deg,
    transparent 0 33px,
    rgb(70 40 18 / 0.06) 33px 34px
  );
}
.sheet__head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding-bottom: 8px;
  border-bottom: 2px solid var(--text);
}
.sheet__title {
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}
.sheet__day {
  font-family: var(--font-display);
  font-size: 1.05rem;
  font-weight: 650;
}
.sheet__row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto 48px;
  align-items: center;
  gap: 10px;
  padding: 9px 0;
  border-bottom: 1px dashed var(--border-strong);
}
.sheet__row:last-child {
  border-bottom: 0;
}
.sheet__name {
  display: flex;
  flex-direction: column;
  min-width: 0;
  font-weight: 700;
  font-size: 0.88rem;
}
.sheet__name small {
  font-weight: 500;
  font-size: 0.72rem;
  color: var(--text-subtle);
}
.sheet__qty {
  font-family: var(--font-display);
  font-size: 1.35rem;
  font-weight: 700;
  text-align: right;
  font-variant-numeric: tabular-nums;
}
.bubble {
  position: absolute;
  right: -18px;
  bottom: 0;
  display: flex;
  gap: 8px;
  align-items: flex-start;
  max-width: 290px;
  padding: 10px 12px 16px;
  border-radius: 12px 12px 3px 12px;
  background: var(--chat-bubble);
  color: var(--chat-text);
  font-size: 0.8rem;
  line-height: 1.4;
  box-shadow: 0 14px 30px -12px rgb(0 0 0 / 0.5);
  transform: rotate(1.2deg);
}
.bubble :deep(svg) {
  flex: none;
  margin-top: 1px;
  color: var(--success);
}
.bubble__time {
  position: absolute;
  right: 10px;
  bottom: 3px;
  font-size: 10px;
  color: var(--chat-meta);
}
@media (max-width: 1180px) {
  .bubble {
    right: 0;
  }
}
</style>
