<script setup>
import { VNodeRenderer } from '@/@layouts/components/VNodeRenderer'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import { themeConfig } from '@themeConfig'
import authV2RegisterIllustrationBorderedDark from '@images/pages/auth-v2-register-illustration-bordered-dark.png'
import authV2RegisterIllustrationBorderedLight from '@images/pages/auth-v2-register-illustration-bordered-light.png'
import authV2RegisterIllustrationDark from '@images/pages/auth-v2-register-illustration-dark.png'
import authV2RegisterIllustrationLight from '@images/pages/auth-v2-register-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { onBeforeUnmount, ref } from "vue"
import { useAuthStore } from "@core/stores/authStore"

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const imageVariant = useGenerateImageVariant(authV2RegisterIllustrationLight, authV2RegisterIllustrationDark, authV2RegisterIllustrationBorderedLight, authV2RegisterIllustrationBorderedDark, true)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

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

    await authStore.requestRegisterToken(data)
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
  },
)
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 100px;"
        >
          <VImg
            max-width="500"
            :src="imageVariant"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask flip-in-rtl"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 pa-6"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Adventure starts here 🚀
          </h4>
          <p class="mb-0">
            Make your work management easy and fun!
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
                  Send Verification Token
                </VBtn>
              </VCol>

              <VCol
                v-if="step !== 'token'"
                cols="12"
                class="text-center text-base"
              >
                <span class="d-inline-block">Already have an account?</span>
                <RouterLink
                  class="text-primary ms-1 d-inline-block"
                  :to="{ name: 'login' }"
                >
                  Sign in instead
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
.layout-blank {
  .auth-wrapper {
    min-block-size: 100dvh;
  }

  .auth-v1-top-shape,
  .auth-v1-bottom-shape {
    position: absolute;
  }

  .auth-footer-mask {
    position: absolute;
    inset-block-end: 0;
    min-inline-size: 100%;
  }

  .auth-card {
    z-index: 1 !important;
  }

  .auth-illustration {
    z-index: 1;
  }

  .auth-v1-top-shape {
    inset-block-start: -77px;
    inset-inline-start: -45px;
  }

  .auth-v1-bottom-shape {
    inset-block-end: -58px;
    inset-inline-end: -58px;
  }

  @media (min-width: 1264px), (max-width: 959px) and (min-width: 450px) {
    .v-otp-input .v-otp-input__content {
      gap: 1rem;
    }
  }
}

@media (min-width: 960px) {
  .skin--bordered {
    .auth-card-v2 {
      border-inline-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)) !important;
    }
  }
}

.auth-logo {
  position: absolute;
  z-index: 2;
  inset-block-start: 2rem;
  inset-inline-start: 2.3rem;
}

.auth-title {
  font-size: 1.375rem;
  font-weight: 700;
  letter-spacing: 0.25px;
  line-height: 1.5rem;
  text-transform: capitalize;
}
</style>
