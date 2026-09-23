<script setup lang="ts">
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { PhEnvelopeSimple, PhLockSimple, PhSparkle } from '@phosphor-icons/vue'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiField from '@/components/ui/UiField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiFormErrors from '@/components/ui/UiFormErrors.vue'
import { useForm } from '@/lib/form'
import { useAuth } from '@/stores/auth'
import { DEMO } from '@/config'

const auth = useAuth()
const route = useRoute()
const router = useRouter()
const form = useForm({ email: '', password: '' })

async function submit() {
  const ok = await form.submit(async () => {
    await auth.login(form.data.email, form.data.password)
    return true
  })
  if (ok) {
    const next =
      typeof route.query.next === 'string' && route.query.next.startsWith('/')
        ? route.query.next
        : '/'
    await router.replace(next)
  }
}

function useDemo(email: string) {
  form.data.email = email
  form.data.password = DEMO.password
  submit()
}
</script>

<template>
  <AuthLayout>
    <div class="stack stack-lg">
      <div class="stack stack-sm">
        <h1>{{ $t('auth.signInTitle') }}</h1>
        <p class="muted">{{ $t('auth.signInSubtitle') }}</p>
      </div>

      <form class="stack" novalidate @submit.prevent="submit">
        <UiFormErrors
          :errors="form.errors.value"
          :message="form.message.value"
          :trigger="form.submitted.value"
        />
        <UiField id="f-email" :label="$t('auth.email')" :error="form.error('email')">
          <template #default="{ id, describedby, invalid }">
            <UiInput
              :id="id"
              v-model="form.data.email"
              type="email"
              autocomplete="username"
              inputmode="email"
              :icon="PhEnvelopeSimple"
              :invalid="invalid"
              :describedby="describedby"
              required
            />
          </template>
        </UiField>
        <UiField id="f-password" :label="$t('auth.password')" :error="form.error('password')">
          <template #default="{ id, describedby, invalid }">
            <UiInput
              :id="id"
              v-model="form.data.password"
              type="password"
              autocomplete="current-password"
              :icon="PhLockSimple"
              :invalid="invalid"
              :describedby="describedby"
              required
            />
          </template>
        </UiField>
        <UiButton type="submit" size="lg" block :loading="form.processing.value">
          {{ form.processing.value ? $t('auth.signingIn') : $t('auth.signIn') }}
        </UiButton>
      </form>

      <div class="demo">
        <div class="demo__text">
          <p class="strong">{{ $t('auth.demoTitle') }}</p>
          <p class="small muted">{{ $t('auth.demoText') }}</p>
        </div>
        <div class="demo__buttons">
          <UiButton
            variant="soft"
            :icon="PhSparkle"
            :disabled="form.processing.value"
            @click="useDemo(DEMO.email)"
            >{{ $t('auth.useDemo') }}</UiButton
          >
          <UiButton
            variant="ghost"
            size="sm"
            :disabled="form.processing.value"
            @click="useDemo(DEMO.staffEmail)"
            >{{ $t('auth.useDemoStaff') }}</UiButton
          >
        </div>
      </div>

      <p class="small muted">
        {{ $t('auth.noAccount') }}
        <RouterLink :to="{ name: 'register' }" class="strong">{{
          $t('auth.createAccount')
        }}</RouterLink>
      </p>
    </div>
  </AuthLayout>
</template>

<style scoped>
.demo {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm), var(--highlight);
}
.demo__buttons {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 6px;
}
@media (max-width: 480px) {
  .demo {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
