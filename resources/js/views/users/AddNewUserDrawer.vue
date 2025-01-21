<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useToast } from "vue-toastification"

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
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
const firstName = ref('')
const lastName = ref('')
const email = ref('')

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const sendData = async () => {
  try {
    const res = await $api('/user', {
      method: 'POST',
      body: {
        first_name: firstName.value,
        last_name: lastName.value,
        email: email.value,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors
      },
    })

    if (res) {
      $toast.success("User added successfully!")
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
      title="Add New User"
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
                  v-model="firstName"
                  label="First Name"
                  placeholder="Tzvi"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="lastName"
                  label="Last Name"
                  placeholder="Gettenberg"
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
