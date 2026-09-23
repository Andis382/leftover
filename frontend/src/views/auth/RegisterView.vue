<script setup lang="ts">
import { useRouter, RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { PhBuildings, PhEnvelopeSimple, PhLockSimple, PhUser } from '@phosphor-icons/vue'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiField from '@/components/ui/UiField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiFormErrors from '@/components/ui/UiFormErrors.vue'
import { useForm } from '@/lib/form'
import { useAuth } from '@/stores/auth'
import type { Locale } from '@/i18n'

const auth = useAuth()
const router = useRouter()
const { locale } = useI18n()
const form = useForm({ name: '', organizationName: '', email: '', password: '' })

async function submit() {
  const ok = await form.submit(async () => {
    await auth.register({ ...form.data, locale: locale.value as Locale })
    return true
  })
  if (ok) await router.replace('/')
}
</script>

<template>
  <AuthLayout>
    <div class="stack stack-lg">
      <div class="stack stack-sm">
        <h1>{{ $t('auth.registerTitle') }}</h1>
        <p class="muted">{{ $t('auth.registerSubtitle') }}</p>
      </div>
      <form class="stack" novalidate @submit.prevent="submit">
        <UiFormErrors
          :errors="form.errors.value"
          :message="form.message.value"
          :trigger="form.submitted.value"
        />
        <UiField id="f-name" :label="$t('auth.yourName')" :error="form.error('name')" required>
          <template #default="{ id, describedby, invalid }">
            <UiInput
              :id="id"
              v-model="form.data.name"
              autocomplete="name"
              :icon="PhUser"
              :invalid="invalid"
              :describedby="describedby"
              required
            />
          </template>
        </UiField>
        <UiField
          id="f-organizationName"
          :label="$t('auth.organizationName')"
          :error="form.error('organizationName')"
          required
        >
          <template #default="{ id, describedby, invalid }">
            <UiInput
              :id="id"
              v-model="form.data.organizationName"
              autocomplete="organization"
              :icon="PhBuildings"
              :invalid="invalid"
              :describedby="describedby"
              required
            />
          </template>
        </UiField>
        <UiField id="f-email" :label="$t('auth.email')" :error="form.error('email')" required>
          <template #default="{ id, describedby, invalid }">
            <UiInput
              :id="id"
              v-model="form.data.email"
              type="email"
              autocomplete="email"
              inputmode="email"
              :icon="PhEnvelopeSimple"
              :invalid="invalid"
              :describedby="describedby"
              required
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
          <template #default="{ id, describedby, invalid }">
            <UiInput
              :id="id"
              v-model="form.data.password"
              type="password"
              autocomplete="new-password"
              :icon="PhLockSimple"
              :invalid="invalid"
              :describedby="describedby"
              minlength="8"
              required
            />
          </template>
        </UiField>
        <UiButton type="submit" size="lg" block :loading="form.processing.value">{{
          $t('auth.register')
        }}</UiButton>
      </form>
      <p class="small muted">
        {{ $t('auth.haveAccount') }}
        <RouterLink :to="{ name: 'login' }" class="strong">{{ $t('auth.signIn') }}</RouterLink>
      </p>
    </div>
  </AuthLayout>
</template>
