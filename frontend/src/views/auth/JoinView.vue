<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { PhEnvelopeSimple, PhLinkBreak, PhLockSimple, PhUser } from '@phosphor-icons/vue'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiField from '@/components/ui/UiField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiFormErrors from '@/components/ui/UiFormErrors.vue'
import UiEmpty from '@/components/ui/UiEmpty.vue'
import UiSkeleton from '@/components/ui/UiSkeleton.vue'
import { api } from '@/lib/api'
import { useForm } from '@/lib/form'
import { useAuth } from '@/stores/auth'
import type { Locale } from '@/i18n'

type Invitation = { organizationName: string; role: string; name: string | null }

const route = useRoute()
const router = useRouter()
const auth = useAuth()
const { locale, t } = useI18n()
const token = String(route.params.token)
const invitation = ref<Invitation | null>(null)
const invalid = ref(false)
const form = useForm({ name: '', email: '', password: '' })

onMounted(async () => {
  try {
    invitation.value = await api.get<Invitation>(`/auth/invitations/${encodeURIComponent(token)}`)
    form.data.name = invitation.value.name ?? ''
  } catch {
    invalid.value = true
  }
})

async function submit() {
  const ok = await form.submit(async () => {
    await auth.join({ token, ...form.data, locale: locale.value as Locale })
    return true
  })
  if (ok) await router.replace('/')
}

function roleLabel(role: string) {
  const key = `roles.${role}`
  const label = t(key)
  return label === key ? role : label
}
</script>

<template>
  <AuthLayout>
    <UiEmpty v-if="invalid" :icon="PhLinkBreak" :title="$t('auth.invitationInvalid')" />
    <UiSkeleton v-else-if="!invitation" :lines="6" height="18px" />
    <div v-else class="stack stack-lg">
      <div class="stack stack-sm">
        <h1>{{ $t('auth.joinTitle', { org: invitation.organizationName }) }}</h1>
        <p class="muted">
          {{ $t('auth.joinAs', { role: roleLabel(invitation.role) }) }}
          {{ $t('auth.joinSubtitle') }}
        </p>
      </div>
      <form class="stack" novalidate @submit.prevent="submit">
        <UiFormErrors
          :errors="form.errors.value"
          :message="form.message.value"
          :trigger="form.submitted.value"
        />
        <UiField id="f-name" :label="$t('auth.yourName')" :error="form.error('name')" required>
          <template #default="{ id, describedby, invalid: bad }">
            <UiInput
              :id="id"
              v-model="form.data.name"
              autocomplete="name"
              :icon="PhUser"
              :invalid="bad"
              :describedby="describedby"
            />
          </template>
        </UiField>
        <UiField id="f-email" :label="$t('auth.email')" :error="form.error('email')" required>
          <template #default="{ id, describedby, invalid: bad }">
            <UiInput
              :id="id"
              v-model="form.data.email"
              type="email"
              autocomplete="email"
              :icon="PhEnvelopeSimple"
              :invalid="bad"
              :describedby="describedby"
            />
          </template>
        </UiField>
        <UiField
          id="f-password"
          :label="$t('auth.password')"
          :hint="$t('auth.passwordHint')"
          :error="form.error('password')"
          required
        >
          <template #default="{ id, describedby, invalid: bad }">
            <UiInput
              :id="id"
              v-model="form.data.password"
              type="password"
              autocomplete="new-password"
              :icon="PhLockSimple"
              :invalid="bad"
              :describedby="describedby"
            />
          </template>
        </UiField>
        <UiButton type="submit" size="lg" block :loading="form.processing.value">{{
          $t('auth.join')
        }}</UiButton>
      </form>
    </div>
  </AuthLayout>
</template>
