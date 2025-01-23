<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue"
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
  token: undefined,
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
    startCountdown()
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
      token: credentials.value.token.toUpperCase(),
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

const onTokenInput = () => {
  if (credentials.value.token.length === 6) {
    verifyToken()
  }
}

const countdown = ref(120)
const showResendButton = ref(false)
let countdownInterval

const startCountdown = () => {
  countdown.value = 120
  showResendButton.value = false

  if (countdownInterval) clearInterval(countdownInterval)

  countdownInterval = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--
    } else {
      clearInterval(countdownInterval)
      showResendButton.value = true
    }
  }, 1000)
}

onBeforeUnmount(() => {
  if (countdownInterval) clearInterval(countdownInterval)
})

watch(
  () => credentials.value.token,
  newVal => {
    if (newVal) {
      credentials.value.token = newVal.toUpperCase()
    }
  }
)
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
                  :readonly="step === 'token'"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <VCol
                v-if="step === 'token'"
                cols="12"
              >
                <VLabel for="token">
                  Access Token
                </VLabel>
                <VOtpInput
                  id="token"
                  v-model="credentials.token"
                  type="text"
                  length="6"
                  variant="outlined"
                  :rules="[requiredValidator]"
                  :error="errors.token"
                  :error-messages="errors.token"
                  @update:model-value="onTokenInput"
                />
                <VAlert
                  v-if="errors.token"
                  type="error"
                  outlined
                  dense
                  transition="slide-x-transition"
                  class="mb-3"
                >
                  <template v-if="credentials.token">
                    The provided token is invalid or has expired.
                  </template>
                  <template v-else>
                    Please enter the token sent to your email.
                  </template>
                </VAlert>

                <div
                  v-if="!showResendButton"
                  class="text-center"
                >
                  <p class="text-sm text-gray-500">
                    Resend available in <span class="font-bold">{{ countdown }} seconds</span>.
                  </p>
                </div>
                <VBtn
                  v-else
                  block
                  color="primary"
                  @click="sendToken"
                >
                  Send Token Again
                </VBtn>
              </VCol>

              <VCol cols="12">
                <VBtn
                  v-if="step !== 'token'"
                  block
                  type="submit"
                >
                  Send Login Token
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

.auth-wrapper {
  .auth-logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #fff;
    font-size: 1.5rem;
    font-weight: bold;
  }

  .auth-card-v2 {
    .v-card {
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.85);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      border-radius: 1rem;

      .text-h3 {
        color: #1e3a8a;
      }

      .v-btn {
        background: #1e3a8a !important;
        color: #fff;
        font-weight: bold;
        &:hover {
          background: linear-gradient(135deg, #1e3a8a, #2563eb) !important;
        }
      }

      .v-alert {
        background-color: rgba(255, 0, 0, 0.1);
        color: #b91c1c;
      }
    }
  }
}
</style>
