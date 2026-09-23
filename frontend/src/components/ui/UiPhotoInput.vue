<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { PhCamera, PhImage, PhTrash } from '@phosphor-icons/vue'
import { downscaleImage } from '@/lib/image'

/**
 * Camera-first photo picker. On phones "Take a photo" opens the rear camera directly.
 * Emits a downscaled JPEG File (or null when removed).
 */
const model = defineModel<File | null>({ default: null })

const props = withDefaults(
  defineProps<{
    id?: string
    /** Existing photo URL to show before a new one is chosen */
    currentUrl?: string | null
    aspect?: string
    hint?: string
    busy?: boolean
  }>(),
  { id: undefined, currentUrl: null, aspect: '4 / 3', hint: undefined },
)

const emit = defineEmits<{ picked: [file: File] }>()

const camera = ref<HTMLInputElement | null>(null)
const library = ref<HTMLInputElement | null>(null)
const preview = ref<string | null>(null)
const preparing = ref(false)

const shown = computed(() => preview.value ?? props.currentUrl ?? null)

watch(model, (file) => {
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = file ? URL.createObjectURL(file) : null
})

onBeforeUnmount(() => {
  if (preview.value) URL.revokeObjectURL(preview.value)
})

async function onPick(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) return
  preparing.value = true
  try {
    const small = await downscaleImage(file)
    model.value = small
    emit('picked', small)
  } finally {
    preparing.value = false
  }
}
</script>

<template>
  <div class="photo">
    <div
      class="photo__frame"
      :style="{ aspectRatio: aspect }"
      :class="{ 'photo__frame--empty': !shown }"
    >
      <img v-if="shown" :src="shown" alt="" class="photo__img" />
      <button v-else type="button" class="photo__placeholder" @click="camera?.click()">
        <span class="photo__cam"><PhCamera :size="30" weight="duotone" aria-hidden="true" /></span>
        <span class="photo__cta">{{ $t('common.takePhoto') }}</span>
        <span v-if="hint" class="photo__hint">{{ hint }}</span>
      </button>
      <div v-if="preparing || busy" class="photo__busy"><span class="photo__pulse" /></div>
    </div>
    <div class="photo__actions">
      <button type="button" class="photo__action" @click="camera?.click()">
        <PhCamera :size="18" weight="bold" aria-hidden="true" />
        {{ shown ? $t('common.changePhoto') : $t('common.takePhoto') }}
      </button>
      <button type="button" class="photo__action" @click="library?.click()">
        <PhImage :size="18" weight="bold" aria-hidden="true" /> {{ $t('common.choosePhoto') }}
      </button>
      <button
        v-if="model"
        type="button"
        class="photo__action photo__action--danger"
        @click="model = null"
      >
        <PhTrash :size="18" weight="bold" aria-hidden="true" /> {{ $t('common.removePhoto') }}
      </button>
    </div>
    <input
      :id="id"
      ref="camera"
      type="file"
      accept="image/*"
      capture="environment"
      class="visually-hidden"
      tabindex="-1"
      @change="onPick"
    />
    <input
      ref="library"
      type="file"
      accept="image/*"
      class="visually-hidden"
      tabindex="-1"
      @change="onPick"
    />
  </div>
</template>

<style scoped>
.photo {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.photo__frame {
  position: relative;
  width: 100%;
  overflow: hidden;
  background: var(--surface-sunken);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: inset 0 1px 3px rgb(16 24 40 / 0.06);
}
.photo__frame--empty {
  border: 1.5px dashed var(--border-strong);
  background:
    radial-gradient(circle at 50% 38%, var(--primary-soft), transparent 62%), var(--surface-muted);
}
.photo__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.photo__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 100%;
  border: 0;
  background: transparent;
  color: var(--text-muted);
}
.photo__placeholder:focus-visible {
  outline: none;
  box-shadow: inset 0 0 0 4px var(--focus-ring);
}
.photo__cam {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  color: var(--primary);
  background: var(--surface);
  box-shadow: var(--shadow-md), var(--highlight);
}
.photo__cta {
  font-weight: 700;
  color: var(--text);
}
.photo__hint {
  max-width: 30ch;
  font-size: var(--text-xs);
  text-align: center;
}
.photo__busy {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgb(255 255 255 / 0.55);
  backdrop-filter: blur(2px);
}
.photo__pulse {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: 3px solid var(--primary);
  border-right-color: transparent;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.photo__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.photo__action {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 40px;
  padding: 0 12px;
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text);
  font-size: var(--text-sm);
  font-weight: 600;
  box-shadow: var(--shadow-xs);
}
.photo__action:hover {
  border-color: var(--gray-300);
  background: var(--surface-hover);
}
.photo__action--danger {
  color: var(--danger-text);
}
</style>
