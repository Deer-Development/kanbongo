<script setup>
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { useAuthStore } from "@core/stores/authStore"

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const step = ref('email')
const refVForm = ref()
const authStore = useAuthStore()

const credentials = ref({
  email: '',
  token: '',
})

const errors = ref({
  email: undefined,
  password: undefined,
})

const route = useRoute()
const router = useRouter()

const sendToken = async () => {
  errors.value = {
    email: undefined,
    token: undefined,
  }
  try {
    const data = {
      email: credentials.value.email,
    }

    await authStore.requestLoginToken(data)

    step.value = 'token'
  } catch (error) {
    errors.value = error
  }
}

const verifyToken = async () => {
  errors.value = {
    email: undefined,
    token: undefined,
  }
  try {
    const data = {
      email: credentials.value.email,
      token: credentials.value.token,
    }

    await authStore.verifyLoginToken(data)

    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : '/')
    })
  } catch (error) {
    errors.value = error
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      step.value === 'email' ? sendToken() : verifyToken()
    }
  })
}
</script>

<template>
  <a href="javascript:void(0)">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </a>

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="5"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        />
      </div>
    </VCol>

    <VCol
      cols="12"
      md="7"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-6"
      >
        <VCardText>
          <h4 class="text-h3 font-weight-bold mb-1">
            Welcome to <span class="text-capitalize">{{ themeConfig.app.title }}</span>! 👋🏻
          </h4>
          <p class="mb-0">
            Please follow the steps below to log in.
          </p>
        </VCardText>
        <VCardText>
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.email"
                  label="Email"
                  placeholder="user@email.com"
                  type="email"
                  autofocus
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <VCol cols="12" v-if="step === 'token'">
                <AppTextField
                  v-model="credentials.token"
                  label="Token"
                  placeholder="Enter your login token"
                  :rules="[requiredValidator]"
                  :error-messages="errors.token"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                >
                  {{ step === 'email' ? 'Send Login Token' : 'Verify and Login' }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>


<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
