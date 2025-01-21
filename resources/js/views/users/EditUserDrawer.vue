<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useToast } from "vue-toastification"
import { defineProps, defineEmits, ref, nextTick, onMounted } from 'vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedUser: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'userData',
  'userUpdated',
])

const $toast = useToast()

const isFormValid = ref(false)
const refForm = ref()
const fullName = ref('')
const password = ref('')
const email = ref('')
const contact = ref('')
const role = ref('admin')
const status = ref(true)
const changePasswordAtLogin = ref(false)
const changePassword = ref(false)

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const sendData = async () => {
  try {
    const res = await $api(`/users/${props.selectedUser}`, {
      method: 'PUT',
      body: {
        name: fullName.value,
        role: role.value,
        phone: contact.value,
        password: password.value,
        email: email.value,
        status: status.value,
        changePasswordAtLogin: changePasswordAtLogin.value,
        changePassword: changePassword.value,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors
      },
    })

    if (res) {
      $toast.success("User updated successfully!")
    }

    emit("update:isDrawerOpen", false)
    emit("userUpdated", true)

    await nextTick(() => {
      refForm.value?.reset()
      refForm.value?.resetValidation()
    })

  } catch (err) {
    console.error(err)
  }
}

const submitForm = () => {
  refForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      sendData()
  })
}

const fetchUser = async () => {
  try {
    const res = await $api(`/users/${props.selectedUser}/edit`)

    if (res) {
      fullName.value = res.user.name
      email.value = res.user.email
      contact.value = res.user.phone
      role.value = 'admin'
      status.value = !!res.user.status
      changePasswordAtLogin.value = !!res.user.password_change_required
    }
  } catch (err) {
    console.error(err)
  }
}

watch(() => props.isDrawerOpen, async () => {
  if (props.isDrawerOpen)
    await fetchUser()
})

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      title="Edit User"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="submitForm"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="fullName"
                  :rules="[requiredValidator]"
                  label="Full Name"
                  placeholder="Tzvi Gettenberg"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                  placeholder="tzvi@email.com"
                />
              </VCol>

              <VCol cols="12">
                <VCheckbox
                  v-model="changePassword"
                  :label="`Change password ${changePassword ? '(Yes)' : '(No)'} `"
                  @update:model-value="password = null"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="password"
                  type="password"
                  :rules="changePassword ? [requiredValidator, passwordValidator] : []"
                  label="Password"
                  placeholder="********"
                  :disabled="!changePassword"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="contact"
                  type="number"
                  label="Contact"
                  placeholder="+1-541-754-3010"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="role"
                  label="Select Role"
                  placeholder="Select Role"
                  :rules="[requiredValidator]"
                  :items="[{ title: 'Admin', value: 'admin' }]"
                />
              </VCol>

              <VCol cols="12">
                <VCheckbox
                  v-model="status"
                  :label="`Status ${status ? 'Active' : 'Inactive'}`"
                />
              </VCol>

              <VCol cols="12">
                <VCheckbox
                  v-model="changePasswordAtLogin"
                  :label="`${changePasswordAtLogin ? 'Change' : 'Do not change'} password at login`"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Submit
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
