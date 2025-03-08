<script setup>
import { ref, computed, watch } from 'vue'
import { VForm } from 'vuetify/components'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@core/stores/authStore'
import NotificationsTab from './tabs/NotificationsTab.vue'
import PaymentDetailsTab from './tabs/PaymentDetailsTab.vue'

const toast = useToast()
const authStore = useAuthStore()
const userData = computed(() => authStore.getCurrentUser)
const avatarFile = ref(null)
const avatarPreview = ref(null)
const loading = ref(false)
const successAlert = ref(false)
const dialog = ref(false)
const showTokenInput = ref(false)
const newEmail = ref('')
const token = ref('')
const emailError = ref('')
const tokenError = ref('')
const refEmailForm = ref()
const refTokenForm = ref()
const activeTab = ref(0)

// Get user timezone and timezone list
const userTimezone = ref(userData.value?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone)
const timezones = Intl.supportedValuesOf('timeZone').map(zone => ({
  name: zone.replace(/_/g, ' '),
  value: zone,
}))

const formRules = {
  required: v => !!v || 'This field is required',
  email: v => /.+@.+\..+/.test(v) || 'Please enter a valid email',
  token: v => (v && v.length >= 6) || 'Token must be at least 6 characters',
}

const tabs = [
  {
    title: 'Account Details',
    icon: 'tabler-user',
  },
  {
    title: 'Payment Details',
    icon: 'tabler-credit-card',
  },
  {
    title: 'Notifications',
    icon: 'tabler-bell',
  },
]

const onSubmit = async () => {
  loading.value = true
  try {
    const formData = new FormData()
    formData.append('first_name', userData.value.first_name)
    formData.append('last_name', userData.value.last_name)
    formData.append('phone', userData.value.phone)
    formData.append('timezone', userTimezone.value)
    
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }

    const response = await $api('/user/profile', {
      method: 'POST',
      body: formData,
    })
    console.log(response.data)
    authStore.currentUser = response.data
    useCookie('userData').value = response.data
    toast.success('Profile updated successfully!')
  } catch (error) {
    toast.error('Failed to update profile')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const handleAvatarUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
  }
}

const resetEmailChange = () => {
  dialog.value = false
  showTokenInput.value = false
  newEmail.value = ''
  token.value = ''
  emailError.value = ''
  tokenError.value = ''
  refEmailForm.value?.resetValidation()
  refTokenForm.value?.resetValidation()
}

const submitEmailChange = async () => {
  const { valid } = await refEmailForm.value?.validate()
  if (!valid) return

  loading.value = true
  emailError.value = ''
  
  try {
    await $api('/user/email/send-token', {
      method: 'POST',
      body: { email: newEmail.value }
    })
    
    showTokenInput.value = true
  } catch (error) {
    emailError.value = error.response?._data?.errors?.email?.[0] || 'Failed to send verification token'
  } finally {
    loading.value = false
  }
}

const verifyToken = async () => {
  const { valid } = await refTokenForm.value?.validate()
  if (!valid) return

  loading.value = true
  tokenError.value = ''
  
  try {
    await $api('/user/email/verify-token', {
      method: 'POST',
      body: {
        email: newEmail.value,
        token: token.value
      }
    })
    
    const updatedUser = {
      ...authStore.currentUser,
      email: newEmail.value
    }
    authStore.currentUser = updatedUser
    useCookie('userData').value = updatedUser
    
    resetEmailChange()
    successAlert.value = true
    setTimeout(() => successAlert.value = false, 3000)
  } catch (error) {
    tokenError.value = error.response?._data?.errors?.token?.[0] || 'Invalid token'
  } finally {
    loading.value = false
  }
}

watch(
  () => token.value,
  newVal => {
    if (newVal) {
      token.value = newVal.toUpperCase()
    }
  },
)
</script>

<template>
  <VTabs
    v-model="activeTab"
    grow
    class="mb-6"
  >
    <VTab
      v-for="(tab, index) in tabs"
      :key="index"
      :value="index"
    >
      <VIcon
        :icon="tab.icon"
        size="20"
        class="me-2"
      />
      {{ tab.title }}
    </VTab>
  </VTabs>

  <VWindow v-model="activeTab">
    <!-- Account Details Tab -->
    <VWindowItem :value="0">
      <VRow>
        <VCol cols="12" md="8">
          <VCard class="profile-card mb-6">
            <VCardText>
              <VForm @submit.prevent="onSubmit">
                <div class="d-flex align-center mb-6">
                  <div class="position-relative">
                    <VAvatar
                      size="100"
                      :color="!(avatarPreview || userData.avatar) ? 'primary' : undefined"
                      :variant="!(avatarPreview || userData.avatar) ? 'tonal' : undefined"
                      class="me-4"
                    >
                      <VImg
                        v-if="avatarPreview || userData.avatar"
                        :src="avatarPreview || userData.avatar"
                        cover
                      />
                      <span v-else>{{ userData.avatar_or_initials }}</span>
                    </VAvatar>
                    
                    <VBtn
                      size="x-small"
                      color="primary"
                      icon
                      class="avatar-edit-btn"
                      @click="$refs.fileInput.click()"
                    >
                      <VIcon size="16" icon="tabler-pencil" />
                    </VBtn>
                  </div>
                  
                  <div class="d-flex flex-column">
                    <h2 class="text-h5 mb-2">
                      Profile Settings
                    </h2>
                    <p class="text-body-2 text-medium-emphasis">
                      Click the pencil to update your photo
                    </p>
                    <input
                      ref="fileInput"
                      type="file"
                      accept="image/*"
                      class="d-none"
                      @change="handleAvatarUpload"
                    >
                  </div>
                </div>

                <VRow>
                  <VCol cols="12" md="6">
                    <VTextField
                      v-model="userData.first_name"
                      label="First Name"
                      placeholder="John"
                      :rules="[formRules.required]"
                      persistent-placeholder
                      variant="outlined"
                      density="comfortable"
                    />
                  </VCol>

                  <VCol cols="12" md="6">
                    <VTextField
                      v-model="userData.last_name"
                      label="Last Name"
                      placeholder="Doe"
                      :rules="[formRules.required]"
                      persistent-placeholder
                      variant="outlined"
                      density="comfortable"
                    />
                  </VCol>

                  <VCol cols="12" md="6">
                    <VTextField
                      v-model="userData.phone"
                      label="Phone Number"
                      placeholder="+1 (555) 000-0000"
                      persistent-placeholder
                      variant="outlined"
                      density="comfortable"
                    />
                  </VCol>

                  <VCol cols="12" md="6">
                    <VSelect
                      v-model="userTimezone"
                      :items="timezones"
                      item-title="name"
                      item-value="value"
                      label="Timezone"
                      placeholder="Select your timezone"
                      persistent-placeholder
                      variant="outlined"
                      density="comfortable"
                    />
                  </VCol>
                </VRow>

                <div class="d-flex justify-end gap-3 mt-4">
                  <VBtn
                    color="primary"
                    type="submit"
                    :loading="loading"
                  >
                    Save Changes
                  </VBtn>
                </div>
              </VForm>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="4">
          <VCard class="email-card">
            <VCardTitle class="px-4 py-3">
              <VIcon
                icon="tabler-mail"
                size="20"
                class="me-2"
              />
              Email Settings
            </VCardTitle>

            <VDivider />

            <VCardText class="pt-4">
              <p class="text-body-2 mb-4">
                Current Email:
                <strong>{{ userData.email }}</strong>
              </p>

              <VBtn
                block
                color="primary"
                variant="tonal"
                prepend-icon="tabler-edit"
                @click="dialog = true"
              >
                Change Email
              </VBtn>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VWindowItem>

    <!-- Payment Details Tab -->
    <VWindowItem :value="1">
      <PaymentDetailsTab />
    </VWindowItem>

    <!-- Notifications Tab -->
    <VWindowItem :value="2">
      <NotificationsTab />
    </VWindowItem>
  </VWindow>

  <!-- Email Change Dialog -->
  <VDialog
    v-model="dialog"
    max-width="500"
    @click:outside="resetEmailChange"
  >
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center pa-4">
        <span>Change Email Address</span>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="resetEmailChange"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">
        <VForm
          v-if="!showTokenInput"
          ref="refEmailForm"
          @submit.prevent="submitEmailChange"
        >
          <VTextField
            v-model="newEmail"
            label="New Email Address"
            placeholder="Enter your new email"
            :rules="[formRules.required, formRules.email]"
            :error-messages="emailError"
            variant="outlined"
            density="comfortable"
          />

          <VCardActions class="pa-0 mt-4">
            <VSpacer />
            <VBtn
              variant="tonal"
              color="secondary"
              @click="resetEmailChange"
            >
              Cancel
            </VBtn>
            <VBtn
              color="primary"
              type="submit"
              :loading="loading"
            >
              Send Verification Token
            </VBtn>
          </VCardActions>
        </VForm>

        <VForm
          v-else
          ref="refTokenForm"
          @submit.prevent="verifyToken"
        >
          <p class="text-body-2 mb-4">
            Please enter the verification token sent to {{ newEmail }}
          </p>

          <VLabel for="token" class="mb-2">
            Access Token
          </VLabel>
          
          <VOtpInput
            id="token"
            v-model="token"
            type="text"
            length="6"
            variant="outlined"
            :rules="[formRules.required]"
            :error="!!tokenError"
            :error-messages="tokenError"
          />

          <VAlert
            v-if="tokenError"
            type="error"
            variant="tonal"
            density="compact"
            class="mt-3"
          >
            <template v-if="token">
              The provided token is invalid or has expired.
            </template>
            <template v-else>
              Please enter the token sent to your email.
            </template>
          </VAlert>

          <VCardActions class="pa-0 mt-4">
            <VSpacer />
            <VBtn
              variant="tonal"
              color="secondary"
              @click="resetEmailChange"
            >
              Cancel
            </VBtn>
            <VBtn
              color="primary"
              type="submit"
              :loading="loading"
            >
              Verify Token
            </VBtn>
          </VCardActions>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.profile-card, .email-card {
  border-radius: 8px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  
  &:hover {
    border-color: rgba(var(--v-theme-on-surface), 0.12);
  }
}

.avatar-edit-btn {
  position: absolute;
  bottom: 0;
  right: 12px;
  transform: translateY(25%);
  box-shadow: 0 2px 4px rgba(var(--v-theme-on-surface), 0.1);
}

.v-text-field {
  .v-field__input {
    font-size: 0.875rem;
  }
}

.v-card-title {
  font-size: 1.1rem;
  font-weight: 600;
}

// Tab styles
.v-tabs {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 8px;
  
  .v-tab {
    text-transform: none;
    font-weight: 500;
    
    &--selected {
      font-weight: 600;
    }
  }
}

// Dialog animations
.v-dialog-transition-enter-active,
.v-dialog-transition-leave-active {
  transition: transform 0.2s ease-in-out, opacity 0.2s ease-in-out;
}

.v-dialog-transition-enter-from,
.v-dialog-transition-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

// Adăugăm stiluri pentru OTP input
:deep(.v-otp-input) {
  gap: 8px;
  margin-bottom: 1rem;

  .v-field {
    border-radius: 8px;
    
    &--error {
      border-color: rgb(var(--v-theme-error));
    }
  }
}

.v-alert {
  background-color: rgba(var(--v-theme-error), 0.1);
  color: rgb(var(--v-theme-error));
  font-size: 0.875rem;
}
</style> 